<?php

use App\Exceptions\WorkflowTransitionException;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Funcionario;
use App\Models\Process;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowState;
use App\Models\WorkflowTask;
use App\Models\WorkflowTaskAssignment;
use App\Notifications\WorkflowTransitionNotification;
use App\Services\Workflow\WorkflowTransitionService;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

function adjuntarDocumentoValidado(Process $proceso, string $codigoTipo): Document
{
    $tipo = DocumentType::firstOrCreate(['codigo' => $codigoTipo], ['nombre' => $codigoTipo]);
    $documento = Document::create(['document_type_id' => $tipo->id]);

    $proceso->documentLinks()->create(['document_id' => $documento->id]);
    $documento->validations()->create(['estado' => 'valido', 'validado_en' => now()]);

    return $documento;
}

/**
 * Builds a minimal definition/states/transitions fixture for the tests:
 * borrador -[enviar_revision]-> revision -[aprobar]-> aprobado (final)
 *                                revision -[rechazar, requiere comentario]-> rechazado (final)
 */
function crearWorkflowDePrueba(bool $activo = true): array
{
    $definicion = WorkflowDefinition::create(['codigo' => 'test-workflow', 'nombre' => 'Workflow de prueba', 'activo' => $activo]);

    $borrador = $definicion->states()->create(['codigo' => 'borrador', 'nombre' => 'Borrador', 'es_inicial' => true]);
    $revision = $definicion->states()->create(['codigo' => 'revision', 'nombre' => 'En revisión']);
    $aprobado = $definicion->states()->create(['codigo' => 'aprobado', 'nombre' => 'Aprobado', 'es_final' => true]);
    $rechazado = $definicion->states()->create(['codigo' => 'rechazado', 'nombre' => 'Rechazado', 'es_final' => true]);

    $enviarRevision = $definicion->transitions()->create([
        'from_state_id' => $borrador->id,
        'to_state_id' => $revision->id,
        'codigo' => 'enviar_revision',
        'nombre' => 'Enviar a revisión',
    ]);

    $aprobar = $definicion->transitions()->create([
        'from_state_id' => $revision->id,
        'to_state_id' => $aprobado->id,
        'codigo' => 'aprobar',
        'nombre' => 'Aprobar',
        'permiso_requerido' => 'workflow-test.aprobar',
        'documentos_requeridos' => ['cedula_identidad'],
    ]);

    $rechazar = $definicion->transitions()->create([
        'from_state_id' => $revision->id,
        'to_state_id' => $rechazado->id,
        'codigo' => 'rechazar',
        'nombre' => 'Rechazar',
        'requiere_comentario' => true,
    ]);

    return compact('definicion', 'borrador', 'revision', 'aprobado', 'rechazado', 'enviarRevision', 'aprobar', 'rechazar');
}

function crearProcesoDePrueba(WorkflowDefinition $definicion, WorkflowState $estadoInicial): Process
{
    return Process::create([
        'workflow_definition_id' => $definicion->id,
        'current_state_id' => $estadoInicial->id,
        'subject_type' => Funcionario::class,
        'subject_id' => Funcionario::create(['rut' => fake()->unique()->numerify('########-#'), 'nombre' => 'Sujeto de prueba'])->id,
    ]);
}

test('crear un proceso lo deja en el estado inicial de su workflow', function () {
    ['definicion' => $definicion, 'borrador' => $borrador] = crearWorkflowDePrueba();

    $proceso = crearProcesoDePrueba($definicion, $borrador);

    expect($proceso->currentState->codigo)->toBe('borrador');
    expect($proceso->currentState->es_inicial)->toBeTrue();
});

test('ejecutar una transición válida cambia el estado, audita, registra historial, cierra tareas y notifica', function () {
    Notification::fake();

    ['definicion' => $definicion, 'borrador' => $borrador, 'revision' => $revision, 'enviarRevision' => $enviarRevision] = crearWorkflowDePrueba();

    $proceso = crearProcesoDePrueba($definicion, $borrador);

    $tarea = WorkflowTask::create([
        'process_id' => $proceso->id,
        'workflow_transition_id' => $enviarRevision->id,
        'titulo' => 'Revisar antecedentes',
    ]);

    $responsable = User::factory()->create();
    WorkflowTaskAssignment::create(['workflow_task_id' => $tarea->id, 'user_id' => $responsable->id]);

    $usuario = User::factory()->create();

    $resultado = app(WorkflowTransitionService::class)->execute($proceso, 'enviar_revision', user: $usuario);

    expect($resultado->currentState->codigo)->toBe('revision');
    expect($proceso->refresh()->transitionLogs)->toHaveCount(1);
    expect($proceso->transitionLogs->first()->from_state_id)->toBe($borrador->id);
    expect($proceso->transitionLogs->first()->to_state_id)->toBe($revision->id);

    expect($tarea->refresh()->estado)->toBe('completada');

    expect(AuditLog::where('action', 'workflow.transicion')->count())->toBe(1);

    Notification::assertSentTo($responsable, WorkflowTransitionNotification::class);
});

test('bloquea la transición si falta un documento obligatorio', function () {
    Permission::create(['name' => 'workflow-test.aprobar']);

    ['definicion' => $definicion, 'revision' => $revision] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('workflow-test.aprobar');

    expect(fn () => app(WorkflowTransitionService::class)->execute($proceso, 'aprobar', user: $usuario))
        ->toThrow(WorkflowTransitionException::class);

    expect($proceso->refresh()->currentState->codigo)->toBe('revision');
});

test('bloquea la transición si el documento requerido está cargado pero no validado', function () {
    Permission::create(['name' => 'workflow-test.aprobar']);

    ['definicion' => $definicion, 'revision' => $revision] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);

    $tipo = DocumentType::create(['codigo' => 'cedula_identidad', 'nombre' => 'Cédula de identidad']);
    $documento = Document::create(['document_type_id' => $tipo->id]);
    $proceso->documentLinks()->create(['document_id' => $documento->id]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('workflow-test.aprobar');

    expect(fn () => app(WorkflowTransitionService::class)->execute($proceso, 'aprobar', user: $usuario))
        ->toThrow(WorkflowTransitionException::class);

    expect($proceso->refresh()->currentState->codigo)->toBe('revision');
});

test('permite la transición cuando el documento requerido está cargado y validado', function () {
    Permission::create(['name' => 'workflow-test.aprobar']);

    ['definicion' => $definicion, 'revision' => $revision, 'aprobado' => $aprobado] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);
    adjuntarDocumentoValidado($proceso, 'cedula_identidad');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('workflow-test.aprobar');

    $resultado = app(WorkflowTransitionService::class)->execute($proceso, 'aprobar', user: $usuario);

    expect($resultado->currentState->codigo)->toBe('aprobado');
});

test('bloquea la transición si el usuario no tiene el permiso requerido', function () {
    Permission::create(['name' => 'workflow-test.aprobar']);

    ['definicion' => $definicion, 'revision' => $revision] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);

    $usuario = User::factory()->create();

    expect(fn () => app(WorkflowTransitionService::class)->execute($proceso, 'aprobar', user: $usuario))
        ->toThrow(WorkflowTransitionException::class);

    expect($proceso->refresh()->currentState->codigo)->toBe('revision');
});

test('bloquea la transición si no es válida desde el estado actual', function () {
    ['definicion' => $definicion, 'borrador' => $borrador] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $borrador);

    $usuario = User::factory()->create();

    expect(fn () => app(WorkflowTransitionService::class)->execute($proceso, 'aprobar', user: $usuario))
        ->toThrow(WorkflowTransitionException::class);

    expect($proceso->refresh()->currentState->codigo)->toBe('borrador');
});

test('bloquea cualquier transición si el workflow está inactivo', function () {
    ['definicion' => $definicion, 'borrador' => $borrador] = crearWorkflowDePrueba(activo: false);
    $proceso = crearProcesoDePrueba($definicion, $borrador);

    $usuario = User::factory()->create();

    expect(fn () => app(WorkflowTransitionService::class)->execute($proceso, 'enviar_revision', user: $usuario))
        ->toThrow(WorkflowTransitionException::class);
});

test('bloquea la transición si exige comentario y no se proporciona', function () {
    ['definicion' => $definicion, 'revision' => $revision] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);

    $usuario = User::factory()->create();

    expect(fn () => app(WorkflowTransitionService::class)->execute($proceso, 'rechazar', user: $usuario))
        ->toThrow(WorkflowTransitionException::class);

    expect($proceso->refresh()->currentState->codigo)->toBe('revision');
});

test('permite la transición que exige comentario cuando se proporciona', function () {
    ['definicion' => $definicion, 'revision' => $revision, 'rechazado' => $rechazado] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);

    $usuario = User::factory()->create();

    $resultado = app(WorkflowTransitionService::class)->execute($proceso, 'rechazar', comentario: 'Antecedentes incompletos', user: $usuario);

    expect($resultado->currentState->codigo)->toBe('rechazado');
    expect($resultado->cerrado_en)->not->toBeNull();
});
