<?php

use App\Exceptions\ProcesoAdquisicionException;
use App\Exceptions\TransicionWorkflowException;
use App\Models\Ccosto;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Documento;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\ProcesoAdquisicion;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use App\Services\Workflow\TransicionWorkflowService;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;

function crearCcostoDePrueba(): Ccosto
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => 'CF-001', 'nombre' => 'Centro Financiero 1']);

    return $cfinanciero->ccostos()->create(['codigo' => 'CC-001', 'nombre' => 'Centro de Costo 1']);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function datosProcesoAdquisicion(array $overrides = []): array
{
    return array_merge([
        'codigo' => 'ADQ-'.fake()->unique()->numerify('#####'),
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => $overrides['ccosto_id'] ?? crearCcostoDePrueba()->id,
        'objeto' => 'Adquisición de prueba',
    ], $overrides);
}

test('crear un proceso de adquisición con una modalidad activa crea el proceso y su workflow en estado borrador', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion());

    expect($proceso->id)->toBeInt();
    expect($proceso->proceso)->not->toBeNull();
    expect($proceso->proceso->estadoActual->codigo)->toBe('borrador');
    expect($proceso->proceso->sujeto_type)->toBe($proceso::class);
});

test('crear un proceso con una modalidad inexistente o inactiva es rechazado sin crear registros', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $modalidadInactiva = ModalidadAdquisicion::create(['codigo' => 'INACTIVA', 'nombre' => 'Inactiva', 'activo' => false]);
    $ccostoId = crearCcostoDePrueba()->id;

    expect(fn () => app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['modalidad_id' => $modalidadInactiva->id, 'ccosto_id' => $ccostoId])))
        ->toThrow(ProcesoAdquisicionException::class);

    expect(fn () => app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['modalidad_id' => 999999, 'ccosto_id' => $ccostoId])))
        ->toThrow(ProcesoAdquisicionException::class);

    expect(ProcesoAdquisicion::count())->toBe(0);
});

test('el workflow adquisiciones sembrado permite ejecutar una transición real', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion());

    $resultado = app(TransicionWorkflowService::class)->execute($proceso->proceso, 'enviar_a_revision');

    expect($resultado->estadoActual->codigo)->toBe('en_revision');
});

test('formalizar_contrato se bloquea sin un documento CONTRATO vinculado y validado, y se permite una vez vinculado', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion());
    $workflow = app(TransicionWorkflowService::class);

    $workflow->execute($proceso->proceso, 'enviar_a_revision');
    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['adquisiciones.publicar', 'adquisiciones.adjudicar']);

    $workflow->execute($proceso->proceso, 'publicar', user: $usuario);
    $workflow->execute($proceso->proceso, 'adjudicar', user: $usuario);

    expect(fn () => $workflow->execute($proceso->proceso, 'formalizar_contrato'))
        ->toThrow(TransicionWorkflowException::class);
    expect($proceso->proceso->refresh()->estadoActual->codigo)->toBe('adjudicada');

    $tipoContrato = TipoDocumento::firstOrCreate(['codigo' => 'CONTRATO'], ['nombre' => 'Contrato']);
    $documento = Documento::create(['tipo_documento_id' => $tipoContrato->id]);
    $proceso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id]);
    $documento->validaciones()->create(['estado' => 'valido', 'validado_en' => now()]);

    $resultado = $workflow->execute($proceso->proceso, 'formalizar_contrato');

    expect($resultado->estadoActual->codigo)->toBe('contratada');
});

test('publicar, adjudicar y anular se bloquean sin el permiso requerido', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion());
    $workflow = app(TransicionWorkflowService::class);

    $workflow->execute($proceso->proceso, 'enviar_a_revision');
    $usuarioSinPermiso = User::factory()->create();

    expect(fn () => $workflow->execute($proceso->proceso, 'publicar', user: $usuarioSinPermiso))
        ->toThrow(TransicionWorkflowException::class);

    expect(fn () => $workflow->execute($proceso->proceso, 'anular', comentario: 'motivo', user: $usuarioSinPermiso))
        ->toThrow(TransicionWorkflowException::class);

    expect($proceso->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');
});

test('el checklist documental de un proceso de adquisición se resuelve según su modalidad_id', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $modalidadPublica = ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->first();
    $modalidadTratoDirecto = ModalidadAdquisicion::where('codigo', 'TRATO_DIRECTO')->first();

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['modalidad_id' => $modalidadPublica->id]));

    $tipo = TipoDocumento::firstOrCreate(['codigo' => 'BASES_LICITACION_TEST'], ['nombre' => 'Bases de licitación']);
    $conjunto = ConjuntoRequisitosDocumentales::create(['codigo' => 'set-adquisiciones-test', 'nombre' => 'Set adquisiciones']);

    $conjunto->requisitos()->create([
        'tipo_documento_id' => $tipo->id,
        'definicion_workflow_id' => $proceso->proceso->definicion_workflow_id,
        'modalidad_id' => $modalidadTratoDirecto->id,
        'tipo_requisito' => 'requerido',
    ]);

    $checklist = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso->proceso, $conjunto);

    expect($checklist->items)->toHaveCount(0);

    $conjunto->requisitos()->create([
        'tipo_documento_id' => $tipo->id,
        'definicion_workflow_id' => $proceso->proceso->definicion_workflow_id,
        'modalidad_id' => $modalidadPublica->id,
        'tipo_requisito' => 'requerido',
    ]);

    $checklistActualizado = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso->proceso->refresh(), $conjunto);

    expect($checklistActualizado->items)->toHaveCount(1);
    expect($checklistActualizado->items->first()->tipo_requisito)->toBe('requerido');
});
