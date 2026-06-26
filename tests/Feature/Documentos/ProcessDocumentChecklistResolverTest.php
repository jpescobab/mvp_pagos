<?php

use App\Models\DocumentRequirementSet;
use App\Models\DocumentType;
use App\Models\Funcionario;
use App\Models\Process;
use App\Models\ProcurementModality;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowState;
use App\Services\Documentos\ProcessDocumentChecklistResolver;

/**
 * @return array{0: WorkflowDefinition, 1: WorkflowState}
 */
function crearWorkflowConEstado(): array
{
    $definicion = WorkflowDefinition::create(['codigo' => 'wf-checklist-'.fake()->unique()->numerify('####'), 'nombre' => 'Workflow checklist']);
    $estado = $definicion->states()->create(['codigo' => 'borrador', 'nombre' => 'Borrador', 'es_inicial' => true]);

    return [$definicion, $estado];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function crearProcesoChecklist(WorkflowDefinition $definicion, WorkflowState $estado, array $overrides = []): Process
{
    return Process::create(array_merge([
        'workflow_definition_id' => $definicion->id,
        'current_state_id' => $estado->id,
        'subject_type' => Funcionario::class,
        'subject_id' => Funcionario::create(['rut' => fake()->unique()->numerify('########-#'), 'nombre' => 'Sujeto de prueba'])->id,
    ], $overrides));
}

test('genera items para requisitos que aplican a cualquier modalidad, monto y estado', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $proceso = crearProcesoChecklist($definicion, $estado);

    $tipo = DocumentType::create(['codigo' => 'FACTURA_TEST', 'nombre' => 'Factura de prueba']);
    $set = DocumentRequirementSet::create(['codigo' => 'set-test-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);

    $set->requirements()->create([
        'document_type_id' => $tipo->id,
        'workflow_definition_id' => $definicion->id,
        'tipo_requisito' => 'requerido',
    ]);

    $checklist = app(ProcessDocumentChecklistResolver::class)->resolve($proceso, $set);

    expect($checklist->items)->toHaveCount(1);
    expect($checklist->items->first()->tipo_requisito)->toBe('requerido');
});

test('no incluye requisitos de otra modalidad', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $modalidadA = ProcurementModality::create(['codigo' => 'MOD_A', 'nombre' => 'Modalidad A']);
    $modalidadB = ProcurementModality::create(['codigo' => 'MOD_B', 'nombre' => 'Modalidad B']);
    $proceso = crearProcesoChecklist($definicion, $estado, ['modalidad_id' => $modalidadA->id]);

    $tipo = DocumentType::create(['codigo' => 'TIPO_TEST', 'nombre' => 'Tipo de prueba']);
    $set = DocumentRequirementSet::create(['codigo' => 'set-test-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);

    $set->requirements()->create([
        'document_type_id' => $tipo->id,
        'workflow_definition_id' => $definicion->id,
        'modalidad_id' => $modalidadB->id,
        'tipo_requisito' => 'requerido',
    ]);

    $checklist = app(ProcessDocumentChecklistResolver::class)->resolve($proceso, $set);

    expect($checklist->items)->toHaveCount(0);
});

test('respeta el rango de monto', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $proceso = crearProcesoChecklist($definicion, $estado, ['monto' => 500000]);

    $tipo = DocumentType::create(['codigo' => 'TIPO_TEST', 'nombre' => 'Tipo de prueba']);
    $set = DocumentRequirementSet::create(['codigo' => 'set-test-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);

    $set->requirements()->create([
        'document_type_id' => $tipo->id,
        'workflow_definition_id' => $definicion->id,
        'monto_desde' => 1000000,
        'tipo_requisito' => 'requerido',
    ]);

    $checklist = app(ProcessDocumentChecklistResolver::class)->resolve($proceso, $set);

    expect($checklist->items)->toHaveCount(0);
});

test('un cambio posterior en la regla no altera un checklist ya generado', function () {
    [$definicion, $estado] = crearWorkflowConEstado();
    $proceso = crearProcesoChecklist($definicion, $estado);

    $tipo = DocumentType::create(['codigo' => 'TIPO_TEST', 'nombre' => 'Tipo de prueba']);
    $set = DocumentRequirementSet::create(['codigo' => 'set-test-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);

    $requisito = $set->requirements()->create([
        'document_type_id' => $tipo->id,
        'workflow_definition_id' => $definicion->id,
        'tipo_requisito' => 'opcional',
    ]);

    $checklist = app(ProcessDocumentChecklistResolver::class)->resolve($proceso, $set);
    expect($checklist->items->first()->tipo_requisito)->toBe('opcional');

    $requisito->update(['tipo_requisito' => 'requerido']);

    expect($checklist->items->first()->refresh()->tipo_requisito)->toBe('opcional');
});
