<?php

use App\Exceptions\TransicionWorkflowException;
use App\Models\AsignacionTareaWorkflow;
use App\Models\AuditLog;
use App\Models\DefinicionWorkflow;
use App\Models\Documento;
use App\Models\EstadoWorkflow;
use App\Models\Funcionario;
use App\Models\Proceso;
use App\Models\TareaWorkflow;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Notifications\TransicionWorkflowNotification;
use App\Services\Workflow\TransicionWorkflowService;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

function adjuntarDocumentoValidado(Proceso $proceso, string $codigoTipo): Documento
{
    $tipo = TipoDocumento::firstOrCreate(['codigo' => $codigoTipo], ['nombre' => $codigoTipo]);
    $documento = Documento::create(['tipo_documento_id' => $tipo->id]);

    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id]);
    $documento->validaciones()->create(['estado' => 'valido', 'validado_en' => now()]);

    return $documento;
}

/**
 * Builds a minimal definition/states/transitions fixture for the tests:
 * borrador -[enviar_revision]-> revision -[aprobar]-> aprobado (final)
 *                                revision -[rechazar, requiere comentario]-> rechazado (final)
 */
function crearWorkflowDePrueba(bool $activo = true): array
{
    $definicion = DefinicionWorkflow::create(['codigo' => 'test-workflow', 'nombre' => 'Workflow de prueba', 'activo' => $activo]);

    $borrador = $definicion->estados()->create(['codigo' => 'borrador', 'nombre' => 'Borrador', 'es_inicial' => true]);
    $revision = $definicion->estados()->create(['codigo' => 'revision', 'nombre' => 'En revisión']);
    $aprobado = $definicion->estados()->create(['codigo' => 'aprobado', 'nombre' => 'Aprobado', 'es_final' => true]);
    $rechazado = $definicion->estados()->create(['codigo' => 'rechazado', 'nombre' => 'Rechazado', 'es_final' => true]);

    $enviarRevision = $definicion->transiciones()->create([
        'estado_origen_id' => $borrador->id,
        'estado_destino_id' => $revision->id,
        'codigo' => 'enviar_revision',
        'nombre' => 'Enviar a revisión',
    ]);

    $aprobar = $definicion->transiciones()->create([
        'estado_origen_id' => $revision->id,
        'estado_destino_id' => $aprobado->id,
        'codigo' => 'aprobar',
        'nombre' => 'Aprobar',
        'permiso_requerido' => 'workflow-test.aprobar',
        'documentos_requeridos' => ['cedula_identidad'],
    ]);

    $rechazar = $definicion->transiciones()->create([
        'estado_origen_id' => $revision->id,
        'estado_destino_id' => $rechazado->id,
        'codigo' => 'rechazar',
        'nombre' => 'Rechazar',
        'requiere_comentario' => true,
    ]);

    return compact('definicion', 'borrador', 'revision', 'aprobado', 'rechazado', 'enviarRevision', 'aprobar', 'rechazar');
}

function crearProcesoDePrueba(DefinicionWorkflow $definicion, EstadoWorkflow $estadoInicial): Proceso
{
    return Proceso::create([
        'definicion_workflow_id' => $definicion->id,
        'estado_actual_id' => $estadoInicial->id,
        'sujeto_type' => Funcionario::class,
        'sujeto_id' => Funcionario::create(['rut' => fake()->unique()->numerify('########-#'), 'nombre' => 'Sujeto de prueba'])->id,
    ]);
}

test('crear un proceso lo deja en el estado inicial de su workflow', function () {
    ['definicion' => $definicion, 'borrador' => $borrador] = crearWorkflowDePrueba();

    $proceso = crearProcesoDePrueba($definicion, $borrador);

    expect($proceso->estadoActual->codigo)->toBe('borrador');
    expect($proceso->estadoActual->es_inicial)->toBeTrue();
});

test('ejecutar una transición válida cambia el estado, audita, registra historial, cierra tareas y notifica', function () {
    Notification::fake();

    ['definicion' => $definicion, 'borrador' => $borrador, 'revision' => $revision, 'enviarRevision' => $enviarRevision] = crearWorkflowDePrueba();

    $proceso = crearProcesoDePrueba($definicion, $borrador);

    $tarea = TareaWorkflow::create([
        'proceso_id' => $proceso->id,
        'transicion_workflow_id' => $enviarRevision->id,
        'titulo' => 'Revisar antecedentes',
    ]);

    $responsable = User::factory()->create();
    AsignacionTareaWorkflow::create(['tarea_workflow_id' => $tarea->id, 'user_id' => $responsable->id]);

    $usuario = User::factory()->create();

    $resultado = app(TransicionWorkflowService::class)->execute($proceso, 'enviar_revision', user: $usuario);

    expect($resultado->estadoActual->codigo)->toBe('revision');
    expect($proceso->refresh()->historialTransiciones)->toHaveCount(1);
    expect($proceso->historialTransiciones->first()->estado_origen_id)->toBe($borrador->id);
    expect($proceso->historialTransiciones->first()->estado_destino_id)->toBe($revision->id);

    expect($tarea->refresh()->estado)->toBe('completada');

    expect(AuditLog::where('action', 'workflow.transicion')->count())->toBe(1);

    Notification::assertSentTo($responsable, TransicionWorkflowNotification::class);
});

test('bloquea la transición si falta un documento obligatorio', function () {
    Permission::create(['name' => 'workflow-test.aprobar']);

    ['definicion' => $definicion, 'revision' => $revision] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('workflow-test.aprobar');

    expect(fn () => app(TransicionWorkflowService::class)->execute($proceso, 'aprobar', user: $usuario))
        ->toThrow(TransicionWorkflowException::class);

    expect($proceso->refresh()->estadoActual->codigo)->toBe('revision');
});

test('bloquea la transición si el documento requerido está cargado pero no validado', function () {
    Permission::create(['name' => 'workflow-test.aprobar']);

    ['definicion' => $definicion, 'revision' => $revision] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);

    $tipo = TipoDocumento::create(['codigo' => 'cedula_identidad', 'nombre' => 'Cédula de identidad']);
    $documento = Documento::create(['tipo_documento_id' => $tipo->id]);
    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('workflow-test.aprobar');

    expect(fn () => app(TransicionWorkflowService::class)->execute($proceso, 'aprobar', user: $usuario))
        ->toThrow(TransicionWorkflowException::class);

    expect($proceso->refresh()->estadoActual->codigo)->toBe('revision');
});

test('permite la transición cuando el documento requerido está cargado y validado', function () {
    Permission::create(['name' => 'workflow-test.aprobar']);

    ['definicion' => $definicion, 'revision' => $revision, 'aprobado' => $aprobado] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);
    adjuntarDocumentoValidado($proceso, 'cedula_identidad');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('workflow-test.aprobar');

    $resultado = app(TransicionWorkflowService::class)->execute($proceso, 'aprobar', user: $usuario);

    expect($resultado->estadoActual->codigo)->toBe('aprobado');
});

test('bloquea la transición si el usuario no tiene el permiso requerido', function () {
    Permission::create(['name' => 'workflow-test.aprobar']);

    ['definicion' => $definicion, 'revision' => $revision] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);

    $usuario = User::factory()->create();

    expect(fn () => app(TransicionWorkflowService::class)->execute($proceso, 'aprobar', user: $usuario))
        ->toThrow(TransicionWorkflowException::class);

    expect($proceso->refresh()->estadoActual->codigo)->toBe('revision');
});

test('bloquea la transición si no es válida desde el estado actual', function () {
    ['definicion' => $definicion, 'borrador' => $borrador] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $borrador);

    $usuario = User::factory()->create();

    expect(fn () => app(TransicionWorkflowService::class)->execute($proceso, 'aprobar', user: $usuario))
        ->toThrow(TransicionWorkflowException::class);

    expect($proceso->refresh()->estadoActual->codigo)->toBe('borrador');
});

test('bloquea cualquier transición si el workflow está inactivo', function () {
    ['definicion' => $definicion, 'borrador' => $borrador] = crearWorkflowDePrueba(activo: false);
    $proceso = crearProcesoDePrueba($definicion, $borrador);

    $usuario = User::factory()->create();

    expect(fn () => app(TransicionWorkflowService::class)->execute($proceso, 'enviar_revision', user: $usuario))
        ->toThrow(TransicionWorkflowException::class);
});

test('bloquea la transición si exige comentario y no se proporciona', function () {
    ['definicion' => $definicion, 'revision' => $revision] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);

    $usuario = User::factory()->create();

    expect(fn () => app(TransicionWorkflowService::class)->execute($proceso, 'rechazar', user: $usuario))
        ->toThrow(TransicionWorkflowException::class);

    expect($proceso->refresh()->estadoActual->codigo)->toBe('revision');
});

test('permite la transición que exige comentario cuando se proporciona', function () {
    ['definicion' => $definicion, 'revision' => $revision, 'rechazado' => $rechazado] = crearWorkflowDePrueba();
    $proceso = crearProcesoDePrueba($definicion, $revision);

    $usuario = User::factory()->create();

    $resultado = app(TransicionWorkflowService::class)->execute($proceso, 'rechazar', comentario: 'Antecedentes incompletos', user: $usuario);

    expect($resultado->estadoActual->codigo)->toBe('rechazado');
    expect($resultado->cerrado_en)->not->toBeNull();
});
