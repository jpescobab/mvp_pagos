<?php

use App\Exceptions\ProcesoAdquisicionException;
use App\Models\Ccosto;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Funcionario;
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

function crearFuncionarioParaEdicion(int $ccostoId): Funcionario
{
    return Funcionario::create([
        'rut' => fake()->unique()->numerify('#########'),
        'nombre' => fake()->name(),
        'ccosto_id' => $ccostoId,
        'activo' => true,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function datosProcesoParaEdicion(array $overrides = []): array
{
    $ccostoId = $overrides['ccosto_id'] ?? crearCcostoParaEdicion()->id;

    return array_merge([
        'fecha_inicio' => now()->toDateString(),
        'nombre' => 'Compra de prueba',
        'id_requerimiento' => null,
        'ccosto_id' => $ccostoId,
        'funcionario_requirente_id' => crearFuncionarioParaEdicion($ccostoId)->id,
        'caracteristicas' => 'Características originales',
        'motivo_contratacion' => 'Motivo original',
        'en_plan_compras' => false,
        'id_pac' => null,
        'codigo_bip' => null,
        'convenio_marco' => true,
        'moneda_compra' => 'CLP',
        'monto_estimado_solicitado' => 100000,
        'fecha_paridad' => null,
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
    $proceso = $servicio->crear(datosProcesoParaEdicion(['convenio_marco' => true, 'monto_estimado_solicitado' => 1000]));

    $servicio->actualizar($proceso, array_merge(
        datosProcesoParaEdicion(['ccosto_id' => $proceso->ccosto_id, 'funcionario_requirente_id' => $proceso->funcionario_requirente_id]),
        ['caracteristicas' => 'Características corregidas', 'convenio_marco' => false, 'monto_estimado_solicitado' => 2500],
    ));

    $proceso->refresh();
    expect($proceso->caracteristicas)->toBe('Características corregidas');
    expect($proceso->objeto)->toBe('Características corregidas');
    expect($proceso->modalidad->codigo)->toBe('TRATO_DIRECTO');
    expect($proceso->proceso->modalidad_id)->toBe($proceso->modalidad_id);
    expect((float) $proceso->proceso->monto)->toBe(2500.0);
});

test('cambiar Convenio Marco a No y re-resolver el checklist exige el informe de justificación', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(RequisitosDocumentalesAdquisicionesSeeder::class);

    $servicio = app(ProcesoAdquisicionService::class);
    $resolutor = app(ResolutorChecklistDocumentalProceso::class);
    $conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'adquisiciones')->firstOrFail();
    $informeId = TipoDocumento::where('codigo', 'INFORME_JUSTIFICACION_TRATO_DIRECTO')->value('id');

    $proceso = $servicio->crear(datosProcesoParaEdicion(['convenio_marco' => true]));

    $checklistConvenio = $resolutor->resolve($proceso->proceso, $conjunto);
    expect($checklistConvenio->items->pluck('tipo_documento_id'))->not->toContain($informeId);

    $servicio->actualizar($proceso, array_merge(
        datosProcesoParaEdicion(['ccosto_id' => $proceso->ccosto_id, 'funcionario_requirente_id' => $proceso->funcionario_requirente_id]),
        ['convenio_marco' => false],
    ));

    $checklistTratoDirecto = $resolutor->resolve($proceso->proceso->refresh(), $conjunto);
    expect($checklistTratoDirecto->items->pluck('tipo_documento_id'))->toContain($informeId);
});

test('actualizar un proceso fuera de borrador lanza excepción y no modifica datos', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $servicio = app(ProcesoAdquisicionService::class);
    $proceso = $servicio->crear(datosProcesoParaEdicion(['caracteristicas' => 'Original']));
    $proceso->proceso->update([
        'estado_actual_id' => $proceso->proceso->definicionWorkflow->estados()->where('codigo', 'en_revision')->value('id'),
    ]);

    expect(fn () => $servicio->actualizar($proceso, array_merge(
        datosProcesoParaEdicion(['ccosto_id' => $proceso->ccosto_id, 'funcionario_requirente_id' => $proceso->funcionario_requirente_id]),
        ['caracteristicas' => 'Cambiado'],
    )))->toThrow(ProcesoAdquisicionException::class);

    expect($proceso->refresh()->caracteristicas)->toBe('Original');
});

test('actualizar sin las modalidades base activas es rechazado', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $servicio = app(ProcesoAdquisicionService::class);
    $proceso = $servicio->crear(datosProcesoParaEdicion(['caracteristicas' => 'Original']));

    ModalidadAdquisicion::where('codigo', 'TRATO_DIRECTO')->update(['activo' => false]);

    expect(fn () => $servicio->actualizar($proceso, array_merge(
        datosProcesoParaEdicion(['ccosto_id' => $proceso->ccosto_id, 'funcionario_requirente_id' => $proceso->funcionario_requirente_id]),
        ['caracteristicas' => 'Cambiado', 'convenio_marco' => false],
    )))->toThrow(ProcesoAdquisicionException::class);

    expect($proceso->refresh()->caracteristicas)->toBe('Original');
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
        ->put(route('adquisiciones.procesos.update', $proceso), datosProcesoParaEdicion([
            'ccosto_id' => $proceso->ccosto_id,
            'funcionario_requirente_id' => $proceso->funcionario_requirente_id,
            'caracteristicas' => 'Intento sin permiso',
        ]))
        ->assertForbidden();

    expect($proceso->refresh()->caracteristicas)->toBe('Características originales');
});

test('con permiso, edit entrega el proceso y update en borrador actualiza y redirige al detalle', function () {
    $this->withoutVite();
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoParaEdicion(['caracteristicas' => 'Original']));
    $usuario = usuarioEditorAdquisiciones();

    $this->actingAs($usuario)
        ->get(route('adquisiciones.procesos.edit', $proceso))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('adquisiciones/procesos/editar', shouldExist: false)
            ->where('proceso.codigo', $proceso->codigo)
            ->where('proceso.caracteristicas', 'Original')
            ->where('proceso.convenio_marco', true)
        );

    $response = $this->actingAs($usuario)->put(
        route('adquisiciones.procesos.update', $proceso),
        datosProcesoParaEdicion([
            'ccosto_id' => $proceso->ccosto_id,
            'funcionario_requirente_id' => $proceso->funcionario_requirente_id,
            'caracteristicas' => 'Corregido',
        ]),
    );

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('adquisiciones.procesos.show', $proceso));
    expect($proceso->refresh()->caracteristicas)->toBe('Corregido');
});

test('update de un proceso fuera de borrador refleja el error y no lo modifica', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoParaEdicion(['caracteristicas' => 'Original']));
    $proceso->proceso->update([
        'estado_actual_id' => $proceso->proceso->definicionWorkflow->estados()->where('codigo', 'en_revision')->value('id'),
    ]);

    $response = $this->actingAs(usuarioEditorAdquisiciones())->put(
        route('adquisiciones.procesos.update', $proceso),
        datosProcesoParaEdicion([
            'ccosto_id' => $proceso->ccosto_id,
            'funcionario_requirente_id' => $proceso->funcionario_requirente_id,
            'caracteristicas' => 'Cambiado',
        ]),
    );

    $response->assertSessionHasErrors('modalidad_id');
    expect($proceso->refresh()->caracteristicas)->toBe('Original');
});
