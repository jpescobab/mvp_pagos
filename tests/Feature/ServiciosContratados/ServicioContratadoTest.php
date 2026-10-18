<?php

use App\Exceptions\ServicioContratadoException;
use App\Models\AuditLog;
use App\Models\CasoPagoProveedor;
use App\Models\Contrato;
use App\Models\EstadoWorkflow;
use App\Models\Proveedor;
use App\Models\ServicioContratado;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\User;
use App\Services\Contratos\ContratoService;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use App\Services\ServiciosContratados\PeriodosServicioContratadoService;
use App\Services\ServiciosContratados\ServicioContratadoService;
use Database\Seeders\ServiciosContratadosSeeder;
use Database\Seeders\WorkflowContratosSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-07-15 10:00:00');
    $this->seed(WorkflowContratosSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ServiciosContratadosSeeder::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

function crearProveedorParaServicios(string $rut = '76123456-7'): Proveedor
{
    return Proveedor::firstOrCreate(['rutproveedor' => $rut], ['nombre' => "Proveedor {$rut}"]);
}

/** Contrato con vigencia 2026-01-01..2026-12-31, por defecto en estado aprobado. */
function crearContratoParaServicios(Proveedor $proveedor, string $estado = 'aprobado'): Contrato
{
    $contrato = app(ContratoService::class)->crear([
        'id_institucional' => fake()->unique()->numberBetween(10000, 99999),
        'modalidad_compra' => 'licitacion',
        'tipo_contrato' => 'contrato',
        'referencia' => 'Contrato de servicios',
        'fecha_inicio_vigencia' => '2026-01-01',
        'fecha_fin_vigencia' => '2026-12-31',
        'proveedor_id' => $proveedor->id,
        'tiene_convenio_precio' => false,
        'tiene_calendario_pago' => false,
    ]);

    // Fixture: posiciona el contrato en el estado pedido sin recorrer el workflow.
    $estadoWorkflow = EstadoWorkflow::where('definicion_workflow_id', $contrato->proceso->definicion_workflow_id)
        ->where('codigo', $estado)->firstOrFail();
    $contrato->proceso->update(['estado_actual_id' => $estadoWorkflow->id]);

    return $contrato->refresh();
}

/**
 * @return array<string, mixed>
 */
function datosServicioDePrueba(Contrato $contrato, array $overrides = []): array
{
    return array_merge([
        'contrato_id' => $contrato->id,
        'nombre' => 'Aseo edificio central',
        'categoria' => 'aseo',
        'monto_mensual_esperado' => 1500000,
        'moneda' => 'CLP',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ], $overrides);
}

function crearCasoConPeriodoParaServicios(Proveedor $proveedor, string $sgfId, ?string $periodo, string $estado = 'recibida_finanzas'): CasoPagoProveedor
{
    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = [
        'sgf_id' => $sgfId,
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => $proveedor->rutproveedor,
        'monto' => 1500000.0,
    ];

    $snapshot = SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $sgfId,
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);
    $caso->update(['periodo' => $periodo]);

    // Fixture: posiciona el caso en el estado pedido sin recorrer el workflow de pago.
    $estadoWorkflow = EstadoWorkflow::where('definicion_workflow_id', $caso->proceso->definicion_workflow_id)
        ->where('codigo', $estado)->firstOrFail();
    $caso->proceso->update(['estado_actual_id' => $estadoWorkflow->id]);

    return $caso->refresh();
}

test('registra un servicio sobre un contrato aprobado y lo audita', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $contrato = crearContratoParaServicios(crearProveedorParaServicios());

    $servicio = app(ServicioContratadoService::class)->registrar(datosServicioDePrueba($contrato));

    expect($servicio->contrato_id)->toBe($contrato->id)
        ->and($servicio->estadoDerivado())->toBe('vigente');
    expect(AuditLog::where('auditable_type', $servicio->getMorphClass())->where('auditable_id', $servicio->id)->exists())->toBeTrue();
});

test('rechaza registrar un servicio sobre un contrato no aprobado', function () {
    $contrato = crearContratoParaServicios(crearProveedorParaServicios(), 'borrador');

    app(ServicioContratadoService::class)->registrar(datosServicioDePrueba($contrato));
})->throws(ServicioContratadoException::class, 'aprobado');

test('rechaza una vigencia fuera de la del contrato', function (string $inicio, string $fin) {
    $contrato = crearContratoParaServicios(crearProveedorParaServicios());

    app(ServicioContratadoService::class)->registrar(
        datosServicioDePrueba($contrato, ['fecha_inicio' => $inicio, 'fecha_fin' => $fin]),
    );
})->throws(ServicioContratadoException::class, 'vigencia')->with([
    'inicio anterior al contrato' => ['2025-12-01', '2026-06-30'],
    'fin posterior al contrato' => ['2026-02-01', '2027-01-31'],
    'fin anterior al inicio' => ['2026-06-01', '2026-05-01'],
]);

test('edita un servicio sin cambiar su contrato y rechaza editar uno terminado', function () {
    $contrato = crearContratoParaServicios(crearProveedorParaServicios());
    $otro = crearContratoParaServicios(crearProveedorParaServicios('77111222-3'));
    $servicios = app(ServicioContratadoService::class);
    $servicio = $servicios->registrar(datosServicioDePrueba($contrato));

    $editado = $servicios->actualizar($servicio, datosServicioDePrueba($otro, ['nombre' => 'Aseo y jardines']));

    expect($editado->nombre)->toBe('Aseo y jardines')
        ->and($editado->contrato_id)->toBe($contrato->id);

    $terminado = $servicios->terminar($editado);

    expect($terminado->terminado_en)->not->toBeNull()
        ->and($terminado->estadoDerivado())->toBe('terminado');
    expect(fn () => $servicios->actualizar($terminado, ['nombre' => 'X']))->toThrow(ServicioContratadoException::class);
});

test('deriva el estado de la vigencia y el scope coincide', function () {
    $contrato = crearContratoParaServicios(crearProveedorParaServicios());
    $servicios = app(ServicioContratadoService::class);

    $vencido = $servicios->registrar(datosServicioDePrueba($contrato, ['nombre' => 'V', 'fecha_fin' => '2026-07-14']));
    $porVencer = $servicios->registrar(datosServicioDePrueba($contrato, ['nombre' => 'PV', 'fecha_fin' => '2026-09-13']));
    $vigente = $servicios->registrar(datosServicioDePrueba($contrato, ['nombre' => 'G', 'fecha_fin' => '2026-09-14']));
    $terminado = $servicios->terminar($servicios->registrar(datosServicioDePrueba($contrato, ['nombre' => 'T'])));

    expect($vencido->estadoDerivado())->toBe('vencido')
        ->and($porVencer->estadoDerivado())->toBe('por_vencer')
        ->and($vigente->estadoDerivado())->toBe('vigente')
        ->and($terminado->estadoDerivado())->toBe('terminado');

    foreach ([$vencido, $porVencer, $vigente, $terminado] as $servicio) {
        $ids = ServicioContratado::conEstado($servicio->estadoDerivado())->pluck('id');
        expect($ids->all())->toBe([$servicio->id]);
    }
});

test('cruza los períodos con los casos del proveedor', function () {
    $proveedor = crearProveedorParaServicios();
    $contrato = crearContratoParaServicios($proveedor);
    $servicio = app(ServicioContratadoService::class)->registrar(
        datosServicioDePrueba($contrato, ['fecha_inicio' => '2026-04-10', 'fecha_fin' => '2026-12-31']),
    );

    crearCasoConPeriodoParaServicios($proveedor, 'p-abr', '2026-04', 'pagada_bancoestado');
    crearCasoConPeriodoParaServicios($proveedor, 'p-may', '2026-05', 'cerrada');
    crearCasoConPeriodoParaServicios($proveedor, 'p-jun', '2026-06', 'en_revision_finanzas');
    crearCasoConPeriodoParaServicios($proveedor, 'p-malo', 'junio 2026', 'pagada_bancoestado');
    crearCasoConPeriodoParaServicios(crearProveedorParaServicios('77111222-3'), 'p-otro', '2026-07', 'pagada_bancoestado');

    $periodos = collect(app(PeriodosServicioContratadoService::class)->periodos($servicio))->keyBy('periodo');

    expect($periodos->keys()->all())->toBe(['2026-04', '2026-05', '2026-06', '2026-07'])
        ->and($periodos['2026-04']['estado'])->toBe('pagado')
        ->and($periodos['2026-04']['casos'][0]['sgf_id'])->toBe('p-abr')
        ->and($periodos['2026-05']['estado'])->toBe('pagado')
        ->and($periodos['2026-06']['estado'])->toBe('en_tramite')
        ->and($periodos['2026-07']['estado'])->toBe('sin_caso');
});

test('marca ambiguo el período cuando el proveedor tiene otro servicio concurrente', function () {
    $proveedor = crearProveedorParaServicios();
    $contrato = crearContratoParaServicios($proveedor);
    $servicios = app(ServicioContratadoService::class);
    $aseo = $servicios->registrar(datosServicioDePrueba($contrato, ['fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-12-31']));
    $servicios->registrar(datosServicioDePrueba($contrato, ['nombre' => 'Jardines', 'categoria' => 'mantencion', 'fecha_inicio' => '2026-06-01', 'fecha_fin' => '2026-12-31']));

    crearCasoConPeriodoParaServicios($proveedor, 'a-may', '2026-05', 'pagada_bancoestado');
    crearCasoConPeriodoParaServicios($proveedor, 'a-jun', '2026-06', 'pagada_bancoestado');

    $periodos = collect(app(PeriodosServicioContratadoService::class)->periodos($aseo))->keyBy('periodo');

    expect($periodos['2026-05']['estado'])->toBe('pagado')
        ->and($periodos['2026-06']['estado'])->toBe('ambiguo')
        ->and($periodos['2026-07']['estado'])->toBe('sin_caso');
});

test('el cruce de períodos no altera los casos de pago', function () {
    $proveedor = crearProveedorParaServicios();
    $contrato = crearContratoParaServicios($proveedor);
    $servicio = app(ServicioContratadoService::class)->registrar(datosServicioDePrueba($contrato));
    $caso = crearCasoConPeriodoParaServicios($proveedor, 'intacto', '2026-03', 'en_revision_finanzas');
    $antes = $caso->proceso->refresh()->estado_actual_id;

    app(PeriodosServicioContratadoService::class)->periodos($servicio);

    expect($caso->proceso->refresh()->estado_actual_id)->toBe($antes);
});

test('exige el permiso ver para el listado y crear para registrar', function () {
    $contrato = crearContratoParaServicios(crearProveedorParaServicios());
    $sinPermiso = User::factory()->create();
    $this->actingAs($sinPermiso)->get(route('servicios-contratados.index'))->assertForbidden();
    $this->post(route('servicios-contratados.store'), datosServicioDePrueba($contrato))->assertForbidden();

    $admin = User::factory()->create();
    $admin->givePermissionTo(['servicios_contratados.ver', 'servicios_contratados.crear', 'servicios_contratados.terminar']);
    $this->actingAs($admin);

    $this->get(route('servicios-contratados.index'))->assertOk();

    $respuesta = $this->post(route('servicios-contratados.store'), datosServicioDePrueba($contrato));
    $servicio = ServicioContratado::firstOrFail();
    $respuesta->assertRedirect(route('servicios-contratados.show', $servicio));

    $this->get(route('servicios-contratados.show', $servicio))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('servicios-contratados/show')
            ->where('servicio.estado', 'vigente')
            ->has('periodos'));

    $this->post(route('servicios-contratados.terminar', $servicio))->assertRedirect();
    expect($servicio->refresh()->terminado_en)->not->toBeNull();
});

test('el listado filtra por estado derivado', function () {
    $contrato = crearContratoParaServicios(crearProveedorParaServicios());
    $servicios = app(ServicioContratadoService::class);
    $servicios->registrar(datosServicioDePrueba($contrato, ['nombre' => 'Por vencer', 'fecha_fin' => '2026-08-30']));
    $servicios->registrar(datosServicioDePrueba($contrato, ['nombre' => 'Vigente']));

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('servicios_contratados.ver');

    $this->actingAs($usuario)
        ->get(route('servicios-contratados.index', ['estado' => 'por_vencer']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('servicios.data', 1)
            ->where('servicios.data.0.nombre', 'Por vencer')
            ->where('servicios.data.0.por_vencer', true));
});
