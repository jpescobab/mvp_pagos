<?php

use App\Models\CasoPagoProveedor;
use App\Models\Ccosto;
use App\Models\CorteReportabilidad;
use App\Models\DefinicionInformeRazonado;
use App\Models\EgresoCgu;
use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\PeriodoReportabilidad;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use App\Services\Indicadores\ServicioImportacionIndicadores;
use App\Services\InformesRazonados\InformeRazonadoService;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use App\Services\Workflow\TransicionWorkflowService;
use Carbon\CarbonImmutable;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Database\Seeders\WorkflowInformesRazonadosSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

function crearCcostoDePruebaParaDashboard(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-DASH-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-DASH-{$sufijo}", 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-DASH-{$sufijo}", 'nombre' => 'Centro Financiero 1']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-DASH-{$sufijo}", 'nombre' => 'Centro de Costo 1']);
}

function crearCasoPagoProveedorDePruebaParaDashboard(string $sgfId, string $proveedorNombre = 'Proveedor de Prueba'): CasoPagoProveedor
{
    $proveedor = Proveedor::create([
        'rutproveedor' => fake()->unique()->numerify('########-#'),
        'nombre' => $proveedorNombre,
        'activo' => true,
    ]);

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
        'monto' => 500000.0,
    ];

    $snapshot = SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $normalizado['sgf_id'],
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);

    return app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);
}

function crearProcesoAdquisicionDePruebaParaDashboard(string $codigo): void
{
    app(ProcesoAdquisicionService::class)->crear([
        'codigo' => $codigo,
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => crearCcostoDePruebaParaDashboard()->id,
        'objeto' => 'Compra de equipos de climatización',
    ]);
}

function crearEjecucionInformeRazonadoDePruebaParaDashboard(string $codigoDefinicion): void
{
    $periodo = PeriodoReportabilidad::create([
        'codigo' => 'PERIODO-'.uniqid(),
        'fecha_inicio' => '2026-06-01',
        'fecha_fin' => '2026-06-30',
        'estado' => 'abierto',
    ]);

    $corte = CorteReportabilidad::create([
        'periodo_reportabilidad_id' => $periodo->id,
        'fecha_corte' => now(),
        'estado' => 'publicado',
    ]);

    $definicion = DefinicionInformeRazonado::create([
        'codigo' => $codigoDefinicion,
        'nombre' => 'Informe de prueba',
        'activo' => true,
    ]);

    app(InformeRazonadoService::class)->iniciarEjecucion($definicion, $corte);
}

function crearIndicadorDePruebaParaDashboard(array $atributos): IndicadorEconomico
{
    $importacion = IndicadorEconomicoImportacion::create(['tipo_importacion' => 'diaria_usd', 'estado' => 'success']);

    return IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'nombre' => 'Indicador de prueba',
        'tipo' => 'moneda',
        'periodicidad_valor' => 'diaria',
        'unidad_medida' => 'CLP',
        'moneda_base' => 'CLP',
        'fuente' => 'CMF',
        ...$atributos,
    ]);
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('el dashboard expone los KPIs, indicadores económicos y casos recientes', function () {
    $this->withoutVite();
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $hoy = now();

    // Un caso de pago activo (su Proceso queda abierto, sin cerrado_en).
    $casoActivo = crearCasoPagoProveedorDePruebaParaDashboard('sgf-dash-activo', 'Proveedor Activo');

    // Un caso de pago cerrado, no debe contar en casos_pago_activos. Se recorre
    // el camino de rechazo del workflow real (sin permisos ni documentos
    // exigidos) hasta llegar a un estado final, vía TransicionWorkflowService.
    $casoCerrado = crearCasoPagoProveedorDePruebaParaDashboard('sgf-dash-cerrado', 'Proveedor Cerrado');
    $operador = User::factory()->create();
    $operador->givePermissionTo(['pago_proveedores.gestionar_caso', 'pago_proveedores.revisar_finanzas']);
    $servicioWorkflow = app(TransicionWorkflowService::class);
    $proceso = $casoCerrado->proceso;
    $proceso = $servicioWorkflow->execute($proceso, 'recibir_en_finanzas', user: $operador);
    $proceso = $servicioWorkflow->execute($proceso, 'iniciar_revision_documental', user: $operador);
    $proceso = $servicioWorkflow->execute($proceso, 'observar_finanzas', comentario: 'Observación de prueba', user: $operador);
    $servicioWorkflow->execute($proceso, 'rechazar', comentario: 'Rechazo de prueba', user: $operador);

    // Una adquisición activa.
    crearProcesoAdquisicionDePruebaParaDashboard('ADQ-DASH-001');

    // Un informe razonado en curso.
    crearEjecucionInformeRazonadoDePruebaParaDashboard('INFORME-DASH-001');

    // Egresos CGU dentro del mes actual, incluyendo el primer y el último día
    // (limites del rango de whereYear/whereMonth).
    EgresoCgu::create([
        'numero_egreso' => 'EGR-DASH-001',
        'fecha' => $hoy->copy()->startOfMonth()->toDateString(),
        'monto_total' => 100000,
    ]);
    EgresoCgu::create([
        'numero_egreso' => 'EGR-DASH-002',
        'fecha' => $hoy->copy()->endOfMonth()->toDateString(),
        'monto_total' => 200000,
    ]);
    // Egreso fuera del mes actual, no debe contarse.
    EgresoCgu::create([
        'numero_egreso' => 'EGR-DASH-FUERA',
        'fecha' => $hoy->copy()->subMonths(2)->toDateString(),
        'monto_total' => 300000,
    ]);

    crearIndicadorDePruebaParaDashboard(['codigo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);
    crearIndicadorDePruebaParaDashboard(['codigo' => 'USD', 'fecha_valor' => '2026-06-10', 'valor' => 916.97]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('kpis.casos_pago_activos', 1)
        ->where('kpis.egresos_cgu_mes', 2)
        ->where('kpis.adquisiciones_activas', 1)
        ->where('kpis.informes_en_curso', 1)
        ->has('indicadores', 2)
        ->where('indicadores.0.codigo', 'UF')
        ->where('indicadores.1.codigo', 'USD')
        ->has('casosRecientes', 2)
    );

    $casosRecientes = collect($response->viewData('page')['props']['casosRecientes'])->keyBy('sgf_id');

    expect($casosRecientes['sgf-dash-activo']['proveedor'])->toBe('Proveedor Activo');
    expect($casosRecientes['sgf-dash-activo']['cerrado'])->toBeFalse();

    expect($casosRecientes['sgf-dash-cerrado']['proveedor'])->toBe('Proveedor Cerrado');
    expect($casosRecientes['sgf-dash-cerrado']['cerrado'])->toBeTrue();
});

test('el topbar y el dashboard reflejan el indicador recién importado sin esperar el TTL de la caché', function () {
    $this->withoutVite();

    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 6, 15));

    crearIndicadorDePruebaParaDashboard(['codigo' => 'USD', 'fecha_valor' => '2026-06-12', 'valor' => 900.0]);

    $usuario = User::factory()->create();

    $primeraRespuesta = $this->actingAs($usuario)->get(route('dashboard'));
    $primerasProps = $primeraRespuesta->viewData('page')['props'];

    expect((float) collect($primerasProps['indicadoresTopbar'])->firstWhere('codigo', 'USD')['valor'])->toBe(900.0);
    expect((float) collect($primerasProps['indicadores'])->firstWhere('codigo', 'USD')['valor'])->toBe(900.0);

    Http::fake([
        '*/dolar*' => Http::response(['Dolares' => [['Valor' => '950,00', 'Fecha' => '2026-06-15']]]),
    ]);
    app(ServicioImportacionIndicadores::class)->importarUsd();

    $segundaRespuesta = $this->actingAs($usuario)->get(route('dashboard'));
    $segundasProps = $segundaRespuesta->viewData('page')['props'];

    expect((float) collect($segundasProps['indicadoresTopbar'])->firstWhere('codigo', 'USD')['valor'])->toBe(950.0);
    expect((float) collect($segundasProps['indicadores'])->firstWhere('codigo', 'USD')['valor'])->toBe(950.0);

    CarbonImmutable::setTestNow();
});
