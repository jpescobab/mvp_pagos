<?php

use App\Models\Ccosto;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\RequisitosDocumentalesAdquisicionesSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearCcostoDePruebaParaChecklist(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-CL-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-CL-{$sufijo}", 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-CL-{$sufijo}", 'nombre' => 'Centro Financiero 1']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-CL-{$sufijo}", 'nombre' => 'Centro de Costo 1']);
}

function sembrarRequisitosDocumentalesAdquisiciones(): void
{
    test()->seed(ModalidadesAdquisicionSeeder::class);
    test()->seed(WorkflowAdquisicionesSeeder::class);
    test()->seed(TiposDocumentoSeeder::class);
    test()->seed(RequisitosDocumentalesAdquisicionesSeeder::class);
}

test('el seeder crea los tipos de documento y la matriz de requisitos por modalidad', function () {
    sembrarRequisitosDocumentalesAdquisiciones();

    expect(TipoDocumento::where('codigo', 'CONTRATO')->exists())->toBeTrue();
    expect(TipoDocumento::where('codigo', 'BASES_LICITACION')->exists())->toBeTrue();

    $conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'adquisiciones')->first();
    expect($conjunto)->not->toBeNull();

    $licitacionPublica = ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->first();
    $codigosLicitacionPublica = RequisitoDocumental::where('conjunto_requisitos_documentales_id', $conjunto->id)
        ->where('modalidad_id', $licitacionPublica->id)
        ->with('tipoDocumento')
        ->get()
        ->pluck('tipoDocumento.codigo');

    expect($codigosLicitacionPublica)->toContain('BASES_LICITACION', 'GARANTIA', 'CONTRATO');
});

test('abrir el detalle de un proceso con modalidad licitación pública genera un checklist con bases y garantía', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesAdquisiciones();

    $proceso = app(ProcesoAdquisicionService::class)->crear([
        'codigo' => 'ADQ-CHK-001',
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => crearCcostoDePruebaParaChecklist()->id,
        'objeto' => 'Compra de equipos de climatización',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $page->component('adquisiciones/procesos/show', shouldExist: false);
        $items = $page->toArray()['props']['proceso']['proceso']['checklist']['items'];
        $tiposDocumento = array_column($items, 'tipo_documento');

        expect($tiposDocumento)->toContain('Bases de Licitación');
        expect($tiposDocumento)->toContain('Garantía');
    });
});

test('abrir el detalle de un proceso con modalidad trato directo no exige bases de licitación', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesAdquisiciones();

    $proceso = app(ProcesoAdquisicionService::class)->crear([
        'codigo' => 'ADQ-CHK-002',
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'TRATO_DIRECTO')->value('id'),
        'ccosto_id' => crearCcostoDePruebaParaChecklist()->id,
        'objeto' => 'Contratación directa de mantenimiento',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $page->component('adquisiciones/procesos/show', shouldExist: false);
        $items = $page->toArray()['props']['proceso']['proceso']['checklist']['items'];
        $tiposDocumento = array_column($items, 'tipo_documento');

        expect($tiposDocumento)->not->toContain('Bases de Licitación');
        expect($tiposDocumento)->toContain('Contrato');
    });
});

test('abrir el detalle dos veces no duplica los items del checklist', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesAdquisiciones();

    $proceso = app(ProcesoAdquisicionService::class)->crear([
        'codigo' => 'ADQ-CHK-003',
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => crearCcostoDePruebaParaChecklist()->id,
        'objeto' => 'Compra de mobiliario',
    ]);

    $usuario = User::factory()->create();

    $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));
    $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $cantidadItems = $proceso->proceso->checklist->items()->count();
    $cantidadRequisitosEsperados = RequisitoDocumental::whereHas('conjuntoRequisitos', fn ($query) => $query->where('codigo', 'adquisiciones'))
        ->where('modalidad_id', $proceso->modalidad_id)
        ->count();

    expect($cantidadItems)->toBe($cantidadRequisitosEsperados);
});
