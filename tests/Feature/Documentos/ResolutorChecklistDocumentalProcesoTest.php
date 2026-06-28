<?php

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\Documento;
use App\Models\EstadoWorkflow;
use App\Models\Funcionario;
use App\Models\ModalidadAdquisicion;
use App\Models\Proceso;
use App\Models\TipoDocumento;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;

/**
 * @return array{0: DefinicionWorkflow, 1: EstadoWorkflow}
 */
function crearWorkflowConEstado(): array
{
    $definicion = DefinicionWorkflow::create(['codigo' => 'wf-checklist-'.fake()->unique()->numerify('####'), 'nombre' => 'Workflow checklist']);
    $estado = $definicion->estados()->create(['codigo' => 'borrador', 'nombre' => 'Borrador', 'es_inicial' => true]);

    return [$definicion, $estado];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function crearProcesoChecklist(DefinicionWorkflow $definicion, EstadoWorkflow $estado, array $overrides = []): Proceso
{
    return Proceso::create(array_merge([
        'definicion_workflow_id' => $definicion->id,
        'estado_actual_id' => $estado->id,
        'sujeto_type' => Funcionario::class,
        'sujeto_id' => Funcionario::create(['rut' => fake()->unique()->numerify('########-#'), 'nombre' => 'Sujeto de prueba'])->id,
    ], $overrides));
}

test('genera items para requisitos que aplican a cualquier modalidad, monto y estado', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $proceso = crearProcesoChecklist($definicion, $estado);

    $tipo = TipoDocumento::create(['codigo' => 'FACTURA_TEST', 'nombre' => 'Factura de prueba']);
    $conjunto = ConjuntoRequisitosDocumentales::create(['codigo' => 'set-test-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);

    $conjunto->requisitos()->create([
        'tipo_documento_id' => $tipo->id,
        'definicion_workflow_id' => $definicion->id,
        'tipo_requisito' => 'requerido',
    ]);

    $checklist = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso, $conjunto);

    expect($checklist->items)->toHaveCount(1);
    expect($checklist->items->first()->tipo_requisito)->toBe('requerido');
});

test('no incluye requisitos de otra modalidad', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $modalidadA = ModalidadAdquisicion::create(['codigo' => 'MOD_A', 'nombre' => 'Modalidad A']);
    $modalidadB = ModalidadAdquisicion::create(['codigo' => 'MOD_B', 'nombre' => 'Modalidad B']);
    $proceso = crearProcesoChecklist($definicion, $estado, ['modalidad_id' => $modalidadA->id]);

    $tipo = TipoDocumento::create(['codigo' => 'TIPO_TEST', 'nombre' => 'Tipo de prueba']);
    $conjunto = ConjuntoRequisitosDocumentales::create(['codigo' => 'set-test-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);

    $conjunto->requisitos()->create([
        'tipo_documento_id' => $tipo->id,
        'definicion_workflow_id' => $definicion->id,
        'modalidad_id' => $modalidadB->id,
        'tipo_requisito' => 'requerido',
    ]);

    $checklist = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso, $conjunto);

    expect($checklist->items)->toHaveCount(0);
});

test('respeta el rango de monto', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $proceso = crearProcesoChecklist($definicion, $estado, ['monto' => 500000]);

    $tipo = TipoDocumento::create(['codigo' => 'TIPO_TEST', 'nombre' => 'Tipo de prueba']);
    $conjunto = ConjuntoRequisitosDocumentales::create(['codigo' => 'set-test-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);

    $conjunto->requisitos()->create([
        'tipo_documento_id' => $tipo->id,
        'definicion_workflow_id' => $definicion->id,
        'monto_desde' => 1000000,
        'tipo_requisito' => 'requerido',
    ]);

    $checklist = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso, $conjunto);

    expect($checklist->items)->toHaveCount(0);
});

test('un cambio posterior en la regla no altera un checklist ya generado', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $proceso = crearProcesoChecklist($definicion, $estado);

    $tipo = TipoDocumento::create(['codigo' => 'TIPO_TEST', 'nombre' => 'Tipo de prueba']);
    $conjunto = ConjuntoRequisitosDocumentales::create(['codigo' => 'set-test-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);

    $requisito = $conjunto->requisitos()->create([
        'tipo_documento_id' => $tipo->id,
        'definicion_workflow_id' => $definicion->id,
        'tipo_requisito' => 'opcional',
    ]);

    $checklist = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso, $conjunto);
    expect($checklist->items->first()->tipo_requisito)->toBe('opcional');

    $requisito->update(['tipo_requisito' => 'requerido']);

    expect($checklist->items->first()->refresh()->tipo_requisito)->toBe('opcional');
});

function vincularDocumentoActivo(Proceso $proceso, TipoDocumento $tipo): Documento
{
    $documento = Documento::create(['tipo_documento_id' => $tipo->id, 'titulo' => 'doc-'.fake()->unique()->numerify('####')]);

    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    return $documento;
}

test('un requisito sin ningún documento subido resuelve pendiente sin documento_id', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $proceso = crearProcesoChecklist($definicion, $estado);

    $tipo = TipoDocumento::create(['codigo' => 'TIPO_SYNC_1', 'nombre' => 'Tipo de prueba']);
    $conjunto = ConjuntoRequisitosDocumentales::create(['codigo' => 'set-sync-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);
    $conjunto->requisitos()->create(['tipo_documento_id' => $tipo->id, 'definicion_workflow_id' => $definicion->id, 'tipo_requisito' => 'requerido']);

    $checklist = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso, $conjunto);

    expect($checklist->items->first()->estado_cumplimiento)->toBe('pendiente');
    expect($checklist->items->first()->documento_id)->toBeNull();
});

test('un requisito con un documento subido sin validar resuelve cargado con su documento_id', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $proceso = crearProcesoChecklist($definicion, $estado);

    $tipo = TipoDocumento::create(['codigo' => 'TIPO_SYNC_2', 'nombre' => 'Tipo de prueba']);
    $conjunto = ConjuntoRequisitosDocumentales::create(['codigo' => 'set-sync-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);
    $conjunto->requisitos()->create(['tipo_documento_id' => $tipo->id, 'definicion_workflow_id' => $definicion->id, 'tipo_requisito' => 'requerido']);

    $documento = vincularDocumentoActivo($proceso, $tipo);

    $checklist = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso, $conjunto);

    expect($checklist->items->first()->estado_cumplimiento)->toBe('cargado');
    expect($checklist->items->first()->documento_id)->toBe($documento->id);
});

test('un requisito cuyo documento ya fue validado o rechazado resuelve ese mismo estado', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $proceso = crearProcesoChecklist($definicion, $estado);

    $tipo = TipoDocumento::create(['codigo' => 'TIPO_SYNC_3', 'nombre' => 'Tipo de prueba']);
    $conjunto = ConjuntoRequisitosDocumentales::create(['codigo' => 'set-sync-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);
    $conjunto->requisitos()->create(['tipo_documento_id' => $tipo->id, 'definicion_workflow_id' => $definicion->id, 'tipo_requisito' => 'requerido']);

    $documento = vincularDocumentoActivo($proceso, $tipo);
    $documento->validaciones()->create(['estado' => 'rechazado']);

    $checklistRechazado = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso, $conjunto);
    expect($checklistRechazado->items->first()->estado_cumplimiento)->toBe('rechazado');

    $documento->validaciones()->create(['estado' => 'valido']);

    $checklistValido = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso, $conjunto);
    expect($checklistValido->items->first()->estado_cumplimiento)->toBe('valido');
});

test('con dos documentos activos del mismo tipo, el item queda asociado al más reciente', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $proceso = crearProcesoChecklist($definicion, $estado);

    $tipo = TipoDocumento::create(['codigo' => 'TIPO_SYNC_4', 'nombre' => 'Tipo de prueba']);
    $conjunto = ConjuntoRequisitosDocumentales::create(['codigo' => 'set-sync-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);
    $conjunto->requisitos()->create(['tipo_documento_id' => $tipo->id, 'definicion_workflow_id' => $definicion->id, 'tipo_requisito' => 'requerido']);

    vincularDocumentoActivo($proceso, $tipo);
    $documentoMasReciente = vincularDocumentoActivo($proceso, $tipo);

    $checklist = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso, $conjunto);

    expect($checklist->items->first()->documento_id)->toBe($documentoMasReciente->id);
});
