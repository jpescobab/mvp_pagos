<?php

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Models\User;
use App\Services\PagoProveedores\RevisionEgresoService;
use App\Services\PagoProveedores\ValidacionDocumentoInstanciaService;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
});

test('el listado sin filtro de estado excluye por defecto los casos en estados avanzados o finales', function () {
    $pendiente = crearCasoPagoProveedorDePrueba('sgf-filtro-pendiente');

    $avanzado = crearCasoPagoProveedorDePrueba('sgf-filtro-avanzado');
    $estadoCerrada = $avanzado->proceso->definicionWorkflow->estados()->where('codigo', 'cerrada')->value('id');
    $avanzado->proceso->update(['estado_actual_id' => $estadoCerrada]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.index'));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) use ($pendiente) {
        $sgfIds = array_column($page->toArray()['props']['casos']['data'], 'sgf_id');

        expect($sgfIds)->toContain($pendiente->sgf_id);
        expect($sgfIds)->not->toContain('sgf-filtro-avanzado');
    });
});

test('estado=todos incluye también los casos en estados avanzados o finales', function () {
    $avanzado = crearCasoPagoProveedorDePrueba('sgf-filtro-todos-avanzado');
    $estadoCerrada = $avanzado->proceso->definicionWorkflow->estados()->where('codigo', 'cerrada')->value('id');
    $avanzado->proceso->update(['estado_actual_id' => $estadoCerrada]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.index', ['estado' => 'todos']));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $sgfIds = array_column($page->toArray()['props']['casos']['data'], 'sgf_id');

        expect($sgfIds)->toContain('sgf-filtro-todos-avanzado');
    });
});

test('filtrar por un estado puntual devuelve solo los casos en ese estado', function () {
    $enRevision = crearEscenarioRevision(100000, 'F1')['caso'];
    crearCasoPagoProveedorDePrueba('sgf-filtro-puntual-pendiente');

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.index', ['estado' => 'en_revision_finanzas']));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) use ($enRevision) {
        $sgfIds = array_column($page->toArray()['props']['casos']['data'], 'sgf_id');

        expect($sgfIds)->toBe([$enRevision->sgf_id]);
    });
});

test('un caso en revisión con checklist aprobado y totales verificados expone listo_para_aprobar en true', function () {
    $e = crearEscenarioRevision(200000, 'L1');
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');

    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.index'));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) use ($e) {
        $caso = collect($page->toArray()['props']['casos']['data'])
            ->firstWhere('sgf_id', $e['caso']->sgf_id);

        expect($caso['listo_para_aprobar'])->toBeTrue();
    });
});

test('un caso en revisión sin totales verificados expone listo_para_aprobar en false', function () {
    $e = crearEscenarioRevision(150000, 'L2');
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');

    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.index'));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) use ($e) {
        $caso = collect($page->toArray()['props']['casos']['data'])
            ->firstWhere('sgf_id', $e['caso']->sgf_id);

        expect($caso['listo_para_aprobar'])->toBeFalse();
    });
});

test('un caso fuera de revisión siempre expone listo_para_aprobar en false', function () {
    $pendiente = crearCasoPagoProveedorDePrueba('sgf-listo-fuera-revision');

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.index'));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) use ($pendiente) {
        $caso = collect($page->toArray()['props']['casos']['data'])
            ->firstWhere('sgf_id', $pendiente->sgf_id);

        expect($caso['listo_para_aprobar'])->toBeFalse();
    });
});
