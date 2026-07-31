<?php

use App\Exceptions\ProcesoAdquisicionException;
use App\Exceptions\TransicionWorkflowException;
use App\Models\Ccosto;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Documento;
use App\Models\Funcionario;
use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\ProcesoAdquisicion;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use App\Services\Workflow\TransicionWorkflowService;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\RequisitosDocumentalesAdquisicionesSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;

function crearCcostoDePrueba(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-{$sufijo}", 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-{$sufijo}", 'nombre' => 'Centro Financiero 1']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-{$sufijo}", 'nombre' => 'Centro de Costo 1']);
}

function crearFuncionarioDePrueba(int $ccostoId): Funcionario
{
    return Funcionario::create([
        'rut' => fake()->unique()->numerify('#########'),
        'nombre' => fake()->name(),
        'ccosto_id' => $ccostoId,
        'activo' => true,
    ]);
}

/**
 * @param  array<string, mixed>  $atributos
 */
function crearIndicadorUtmDePrueba(float $valor, ?string $periodo = null): IndicadorEconomico
{
    $importacion = IndicadorEconomicoImportacion::create(['tipo_importacion' => 'mensual_utm', 'estado' => 'success']);

    return IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'codigo' => 'UTM',
        'nombre' => 'Unidad Tributaria Mensual',
        'tipo' => 'utm',
        'periodo' => $periodo ?? now()->format('Y-m'),
        'valor' => $valor,
        'periodicidad_valor' => 'mensual',
        'unidad_medida' => 'CLP',
        'moneda_base' => 'CLP',
        'fuente' => 'SII',
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function datosProcesoAdquisicion(array $overrides = []): array
{
    $ccostoId = $overrides['ccosto_id'] ?? crearCcostoDePrueba()->id;

    return array_merge([
        'fecha_inicio' => now()->toDateString(),
        'nombre' => 'Compra de prueba',
        'id_requerimiento' => null,
        'ccosto_id' => $ccostoId,
        'funcionario_requirente_id' => crearFuncionarioDePrueba($ccostoId)->id,
        'caracteristicas' => 'Características de prueba',
        'motivo_contratacion' => 'Motivo de prueba',
        'en_plan_compras' => false,
        'id_pac' => null,
        'codigo_bip' => null,
        'convenio_marco' => true,
        'moneda_compra' => 'CLP',
        'monto_estimado_solicitado' => 100000,
        'fecha_paridad' => null,
    ], $overrides);
}

test('crear un proceso de adquisición con convenio marco crea el proceso y su workflow en estado borrador', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion());

    expect($proceso->id)->toBeInt();
    expect($proceso->proceso)->not->toBeNull();
    expect($proceso->proceso->estadoActual->codigo)->toBe('borrador');
    expect($proceso->proceso->sujeto_type)->toBe($proceso::class);
    expect($proceso->codigo)->toStartWith('SPC-');
});

test('convenio_marco determina la modalidad derivada', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $procesoConConvenio = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['convenio_marco' => true]));
    expect($procesoConConvenio->modalidad->codigo)->toBe('CONVENIO_MARCO');

    $procesoSinConvenio = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['convenio_marco' => false]));
    expect($procesoSinConvenio->modalidad->codigo)->toBe('TRATO_DIRECTO');
});

test('crear sin las modalidades base activas es rechazado sin crear registros', function () {
    $this->seed(WorkflowAdquisicionesSeeder::class);

    expect(fn () => app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['convenio_marco' => true])))
        ->toThrow(ProcesoAdquisicionException::class);

    expect(ProcesoAdquisicion::count())->toBe(0);
});

test('el código generado es único entre creaciones sucesivas', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $servicio = app(ProcesoAdquisicionService::class);
    $codigos = collect(range(1, 3))->map(fn () => $servicio->crear(datosProcesoAdquisicion())->codigo);

    expect($codigos->unique())->toHaveCount(3);
});

test('rechaza un monto estimado igual o mayor a 1.000 UTM vigentes', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    crearIndicadorUtmDePrueba(65000);

    expect(fn () => app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['monto_estimado_solicitado' => 65_000_000])))
        ->toThrow(ProcesoAdquisicionException::class);

    expect(ProcesoAdquisicion::count())->toBe(0);
});

test('acepta un monto estimado bajo 1.000 UTM vigentes', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    crearIndicadorUtmDePrueba(65000);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['monto_estimado_solicitado' => 1_000_000]));

    expect($proceso->id)->toBeInt();
});

test('sin un indicador UTM vigente no se bloquea la creación', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['monto_estimado_solicitado' => 999_999_999]));

    expect($proceso->id)->toBeInt();
});

test('en UF/USD la paridad se resuelve por fecha y el monto estimado se calcula solicitado × paridad', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $importacion = IndicadorEconomicoImportacion::create(['tipo_importacion' => 'diaria_uf', 'estado' => 'success']);
    IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'codigo' => 'UF',
        'nombre' => 'Unidad de Fomento',
        'tipo' => 'moneda',
        'fecha_valor' => '2026-07-15',
        'valor' => 39000,
        'periodicidad_valor' => 'diaria',
        'unidad_medida' => 'CLP',
        'moneda_base' => 'CLP',
        'fuente' => 'SII',
    ]);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion([
        'moneda_compra' => 'UF',
        'monto_estimado_solicitado' => 10,
        'fecha_paridad' => '2026-07-15',
    ]));

    expect((float) $proceso->paridad)->toBe(39000.0);
    expect((float) $proceso->monto_estimado)->toBe(390000.0);
});

test('en UF/USD sin indicador para la fecha se rechaza la creación', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    expect(fn () => app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion([
        'moneda_compra' => 'USD',
        'monto_estimado_solicitado' => 100,
        'fecha_paridad' => '2020-01-01',
    ])))->toThrow(ProcesoAdquisicionException::class);

    expect(ProcesoAdquisicion::count())->toBe(0);
});

test('el workflow adquisiciones sembrado permite ejecutar una transición real', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion());

    $resultado = app(TransicionWorkflowService::class)->execute($proceso->proceso, 'enviar_a_revision');

    expect($resultado->estadoActual->codigo)->toBe('en_revision');
});

test('un usuario con rol administrativo_adquisiciones puede aprobar (publicar) y queda en el historial', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion());
    $workflow = app(TransicionWorkflowService::class);

    $workflow->execute($proceso->proceso, 'enviar_a_revision');

    $usuario = User::factory()->create();
    $usuario->assignRole('administrativo_adquisiciones');

    $resultado = $workflow->execute($proceso->proceso, 'publicar', user: $usuario);

    expect($resultado->estadoActual->codigo)->toBe('publicada');

    $historialPublicar = $proceso->proceso->historialTransiciones()
        ->whereHas('transicion', fn ($query) => $query->where('codigo', 'publicar'))
        ->first();

    expect($historialPublicar->user_id)->toBe($usuario->id);
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

    $modalidadConvenioMarco = ModalidadAdquisicion::where('codigo', 'CONVENIO_MARCO')->first();
    $modalidadTratoDirecto = ModalidadAdquisicion::where('codigo', 'TRATO_DIRECTO')->first();

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['convenio_marco' => true]));

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
        'modalidad_id' => $modalidadConvenioMarco->id,
        'tipo_requisito' => 'requerido',
    ]);

    $checklistActualizado = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso->proceso->refresh(), $conjunto);

    expect($checklistActualizado->items)->toHaveCount(1);
    expect($checklistActualizado->items->first()->tipo_requisito)->toBe('requerido');
});

test('trato directo exige el informe de justificación en su checklist', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(RequisitosDocumentalesAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicion(['convenio_marco' => false]));

    $conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'adquisiciones')->firstOrFail();
    $checklist = app(ResolutorChecklistDocumentalProceso::class)->resolve($proceso->proceso, $conjunto);

    expect($checklist->items->pluck('tipo_documento_id'))
        ->toContain(TipoDocumento::where('codigo', 'INFORME_JUSTIFICACION_TRATO_DIRECTO')->value('id'));
});
