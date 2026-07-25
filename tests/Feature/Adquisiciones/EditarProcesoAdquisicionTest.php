<?php

use App\Exceptions\ProcesoAdquisicionException;
use App\Models\Ccosto;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\RequisitosDocumentalesAdquisicionesSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearCcostoParaEdicion(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-ED-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-ED-{$sufijo}", 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-ED-{$sufijo}", 'nombre' => 'Centro Financiero']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-ED-{$sufijo}", 'nombre' => 'Centro de Costo']);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function datosProcesoParaEdicion(array $overrides = []): array
{
    return array_merge([
        'codigo' => 'ADQ-ED-'.fake()->unique()->numerify('#####'),
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => $overrides['ccosto_id'] ?? crearCcostoParaEdicion()->id,
        'objeto' => 'Objeto de prueba',
    ], $overrides);
}

function usuarioEditorAdquisiciones(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['adquisiciones.consultar_proceso', 'adquisiciones.editar_proceso']);

    return $usuario;
}

// --- Dominio ---

test('actualizar en borrador actualiza los campos y sincroniza el Proceso asociado', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $servicio = app(ProcesoAdquisicionService::class);
    $proceso = $servicio->crear(datosProcesoParaEdicion(['monto' => 1000]));
    $tratoDirecto = ModalidadAdquisicion::where('codigo', 'TRATO_DIRECTO')->value('id');

    $servicio->actualizar($proceso, [
        'codigo' => $proceso->codigo,
        'modalidad_id' => $tratoDirecto,
        'ccosto_id' => $proceso->ccosto_id,
        'monto' => 2500,
        'objeto' => 'Objeto corregido',
    ]);

    $proceso->refresh();
    expect($proceso->objeto)->toBe('Objeto corregido');
    expect((int) $proceso->modalidad_id)->toBe((int) $tratoDirecto);
    expect((int) $proceso->proceso->modalidad_id)->toBe((int) $tratoDirecto);
    expect((float) $proceso->proceso->monto)->toBe(2500.0);
});

test('cambiar la modalidad y re-resolver el checklist refleja la nueva modalidad', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(RequisitosDocumentalesAdquisicionesSeeder::class);

    $servicio = app(ProcesoAdquisicionService::class);
    $resolutor = app(ResolutorChecklistDocumentalProceso::class);
    $conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'adquisiciones')->firstOrFail();
    $basesId = TipoDocumento::where('codigo', 'BASES_LICITACION')->value('id');

    $proceso = $servicio->crear([
        'codigo' => 'ADQ-ED-CHK',
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => crearCcostoParaEdicion()->id,
        'objeto' => 'Compra',
    ]);

    $checklistPublica = $resolutor->resolve($proceso->proceso, $conjunto);
    expect($checklistPublica->items->pluck('tipo_documento_id'))->toContain($basesId);

    $servicio->actualizar($proceso, [
        'codigo' => 'ADQ-ED-CHK',
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'TRATO_DIRECTO')->value('id'),
        'ccosto_id' => $proceso->ccosto_id,
        'objeto' => 'Compra',
    ]);

    $checklistTrato = $resolutor->resolve($proceso->proceso->refresh(), $conjunto);
    expect($checklistTrato->items->pluck('tipo_documento_id'))->not->toContain($basesId);
});

test('actualizar un proceso fuera de borrador lanza excepción y no modifica datos', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $servicio = app(ProcesoAdquisicionService::class);
    $proceso = $servicio->crear(datosProcesoParaEdicion(['objeto' => 'Original']));
    $proceso->proceso->update([
        'estado_actual_id' => $proceso->proceso->definicionWorkflow->estados()->where('codigo', 'en_revision')->value('id'),
    ]);

    expect(fn () => $servicio->actualizar($proceso, [
        'codigo' => $proceso->codigo,
        'modalidad_id' => $proceso->modalidad_id,
        'ccosto_id' => $proceso->ccosto_id,
        'objeto' => 'Cambiado',
    ]))->toThrow(ProcesoAdquisicionException::class);

    expect($proceso->refresh()->objeto)->toBe('Original');
});

test('actualizar con una modalidad inexistente o inactiva es rechazado', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $servicio = app(ProcesoAdquisicionService::class);
    $proceso = $servicio->crear(datosProcesoParaEdicion(['objeto' => 'Original']));
    $modalidadInactiva = ModalidadAdquisicion::create(['codigo' => 'INAC-ED', 'nombre' => 'Inactiva', 'activo' => false]);

    expect(fn () => $servicio->actualizar($proceso, [
        'codigo' => $proceso->codigo,
        'modalidad_id' => $modalidadInactiva->id,
        'ccosto_id' => $proceso->ccosto_id,
        'objeto' => 'Cambiado',
    ]))->toThrow(ProcesoAdquisicionException::class);

    expect($proceso->refresh()->objeto)->toBe('Original');
});

// --- HTTP ---

test('edit y update responden 403 sin el permiso adquisiciones.editar_proceso', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoParaEdicion());
    $usuarioSinPermiso = User::factory()->create();

    $this->actingAs($usuarioSinPermiso)
        ->get(route('adquisiciones.procesos.edit', $proceso))
        ->assertForbidden();

    $this->actingAs($usuarioSinPermiso)
        ->put(route('adquisiciones.procesos.update', $proceso), [
            'codigo' => $proceso->codigo,
            'modalidad_id' => $proceso->modalidad_id,
            'ccosto_id' => $proceso->ccosto_id,
            'objeto' => 'Intento sin permiso',
        ])
        ->assertForbidden();

    expect($proceso->refresh()->objeto)->toBe('Objeto de prueba');
});

test('con permiso, edit entrega el proceso y update en borrador actualiza y redirige al detalle', function () {
    $this->withoutVite();
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoParaEdicion(['objeto' => 'Original']));
    $usuario = usuarioEditorAdquisiciones();

    $this->actingAs($usuario)
        ->get(route('adquisiciones.procesos.edit', $proceso))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('adquisiciones/procesos/editar', shouldExist: false)
            ->where('proceso.codigo', $proceso->codigo)
            ->where('proceso.objeto', 'Original')
        );

    $response = $this->actingAs($usuario)->put(route('adquisiciones.procesos.update', $proceso), [
        'codigo' => $proceso->codigo,
        'modalidad_id' => $proceso->modalidad_id,
        'ccosto_id' => $proceso->ccosto_id,
        'objeto' => 'Corregido',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('adquisiciones.procesos.show', $proceso));
    expect($proceso->refresh()->objeto)->toBe('Corregido');
});

test('update de un proceso fuera de borrador refleja el error y no lo modifica', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoParaEdicion(['objeto' => 'Original']));
    $proceso->proceso->update([
        'estado_actual_id' => $proceso->proceso->definicionWorkflow->estados()->where('codigo', 'en_revision')->value('id'),
    ]);

    $response = $this->actingAs(usuarioEditorAdquisiciones())->put(route('adquisiciones.procesos.update', $proceso), [
        'codigo' => $proceso->codigo,
        'modalidad_id' => $proceso->modalidad_id,
        'ccosto_id' => $proceso->ccosto_id,
        'objeto' => 'Cambiado',
    ]);

    $response->assertSessionHasErrors('modalidad_id');
    expect($proceso->refresh()->objeto)->toBe('Original');
});

test('update acepta guardar sin cambiar el código (unique ignora el propio registro)', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoParaEdicion(['codigo' => 'ADQ-ED-MISMO', 'objeto' => 'Original']));

    $response = $this->actingAs(usuarioEditorAdquisiciones())->put(route('adquisiciones.procesos.update', $proceso), [
        'codigo' => 'ADQ-ED-MISMO',
        'modalidad_id' => $proceso->modalidad_id,
        'ccosto_id' => $proceso->ccosto_id,
        'objeto' => 'Corregido',
    ]);

    $response->assertSessionHasNoErrors();
    expect($proceso->refresh()->objeto)->toBe('Corregido');
});

test('update rechaza un código ya usado por otro proceso', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $servicio = app(ProcesoAdquisicionService::class);
    $servicio->crear(datosProcesoParaEdicion(['codigo' => 'ADQ-ED-OCUPADO']));
    $proceso = $servicio->crear(datosProcesoParaEdicion(['codigo' => 'ADQ-ED-PROPIO']));

    $response = $this->actingAs(usuarioEditorAdquisiciones())->put(route('adquisiciones.procesos.update', $proceso), [
        'codigo' => 'ADQ-ED-OCUPADO',
        'modalidad_id' => $proceso->modalidad_id,
        'ccosto_id' => $proceso->ccosto_id,
        'objeto' => 'Cambiado',
    ]);

    $response->assertSessionHasErrors('codigo');
});
