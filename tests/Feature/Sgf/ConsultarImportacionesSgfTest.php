<?php

use App\Models\CasoPagoProveedor;
use App\Models\DefinicionWorkflow;
use App\Models\Documento;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\RegistroContableCgu;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\RequisitosDocumentalesPagoProveedoresSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\TiposProcesoPagoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Deja un caso REEMBOLSO listo para Egreso: tipo de proceso clasificado,
 * Traspaso registrado, checklist obligatorio (Factura + Comprobante,
 * universales) cargado y proveedor identificado. Cada parámetro permite
 * omitir un requisito puntual para probar el criterio "listo_para_egreso".
 */
function dejarCasoListoParaEgreso(
    CasoPagoProveedor $caso,
    bool $conTipoProceso = true,
    bool $conTraspaso = true,
    bool $conChecklistCompleto = true,
): void {
    if ($conTipoProceso) {
        $tipoReembolso = TipoProcesoPago::where('codigo', 'REEMBOLSO')->firstOrFail();
        $caso->proceso->update(['tipo_proceso_pago_id' => $tipoReembolso->id]);
    }

    if ($conTraspaso) {
        RegistroContableCgu::create([
            'caso_pago_proveedor_id' => $caso->id,
            'numero_registro' => 'TR-1',
            'fecha_registro' => now()->toDateString(),
            'monto' => 100000,
        ]);
    }

    if ($conChecklistCompleto) {
        foreach (['FACTURA', 'COMPROBANTE'] as $codigo) {
            $tipoDocumento = TipoDocumento::where('codigo', $codigo)->firstOrFail();
            $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => "{$codigo}.pdf"]);
            $caso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);
        }
    }
}

/**
 * Crea un caso_pago_proveedor con su Proceso, listo para enlazarse a un
 * snapshot por sgf_id. Si $rutProveedor coincide con un Proveedor existente,
 * queda "identificado" (proveedor_id no nulo).
 */
function crearCasoPagoProveedorParaImportacion(string $sgfId, string $rutProveedor, ?int $proveedorId = null): CasoPagoProveedor
{
    $caso = CasoPagoProveedor::create([
        'sgf_id' => $sgfId,
        'proveedor_id' => $proveedorId,
        'rut_proveedor' => $rutProveedor,
        'monto' => 100000,
        'sgf_status' => 'EN_TRAMITE',
    ]);

    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();
    Proceso::create([
        'definicion_workflow_id' => $definicion->id,
        'estado_actual_id' => $definicion->estados()->where('es_inicial', true)->value('id'),
        'sujeto_type' => CasoPagoProveedor::class,
        'sujeto_id' => $caso->id,
        'monto' => 100000,
    ]);

    return $caso->refresh();
}

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
});

test('un usuario autenticado puede listar las importaciones SGF ordenadas de la más reciente a la más antigua', function () {
    $anterior = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now()->subDay(),
        'finalizado_en' => now()->subDay(),
        'total_elementos' => 3,
        'estado' => 'completado',
    ]);
    $reciente = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'verificar_caso',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'en_progreso',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.index', ['estado' => 'todos']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('sgf/importaciones/index')
        ->has('importaciones.data', 2)
        ->where('importaciones.data.0.id', $reciente->id)
        ->where('importaciones.data.1.id', $anterior->id)
        ->where('importaciones.data.1.total_elementos', 3)
        ->where('importaciones.data.1.estado', 'completado')
    );
});

test('el listado se puede filtrar por un término de búsqueda que coincide con el tipo o el usuario que la inició', function () {
    $usuarioIniciador = User::factory()->create(['name' => 'Ana Iniciadora']);

    $porTipo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'verificar_caso',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);
    $porUsuario = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_por' => $usuarioIniciador->id,
        'iniciado_en' => now()->subHour(),
        'estado' => 'completado',
    ]);
    TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now()->subHours(2),
        'estado' => 'completado',
    ]);

    $usuario = User::factory()->create();

    $porTipoResponse = $this->actingAs($usuario)->get(route('sgf.importaciones.index', ['q' => 'verificar_caso', 'estado' => 'todos']));
    $porTipoResponse->assertInertia(fn (Assert $page) => $page
        ->has('importaciones.data', 1)
        ->where('importaciones.data.0.id', $porTipo->id)
    );

    $porUsuarioResponse = $this->actingAs($usuario)->get(route('sgf.importaciones.index', ['q' => 'Ana Iniciadora', 'estado' => 'todos']));
    $porUsuarioResponse->assertInertia(fn (Assert $page) => $page
        ->has('importaciones.data', 1)
        ->where('importaciones.data.0.id', $porUsuario->id)
    );
});

test('el listado queda vacío sin error cuando el término de búsqueda no coincide con nada', function () {
    TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.index', ['q' => 'termino-inexistente', 'estado' => 'todos']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('importaciones.data', 0)
    );
});

test('por defecto el listado excluye los trabajos de importación en estado completado', function () {
    TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);
    $enProgreso = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now()->subMinute(),
        'estado' => 'en_progreso',
    ]);
    $error = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now()->subMinutes(2),
        'estado' => 'error',
    ]);
    $huerfano = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now()->subMinutes(3),
        'estado' => 'huerfano',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filtroEstado', null)
        ->has('importaciones.data', 3)
        ->where('importaciones.data.0.id', $enProgreso->id)
        ->where('importaciones.data.1.id', $error->id)
        ->where('importaciones.data.2.id', $huerfano->id)
    );
});

test('el filtro estado=todos incluye también los trabajos completados', function () {
    $completado = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);
    $enProgreso = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now()->subMinute(),
        'estado' => 'en_progreso',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.index', ['estado' => 'todos']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filtroEstado', 'todos')
        ->has('importaciones.data', 2)
        ->where('importaciones.data.0.id', $completado->id)
        ->where('importaciones.data.1.id', $enProgreso->id)
    );
});

test('el filtro por un estado puntual muestra únicamente los trabajos en ese estado', function () {
    TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);
    TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now()->subMinute(),
        'estado' => 'en_progreso',
    ]);
    $error = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now()->subMinutes(2),
        'estado' => 'error',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.index', ['estado' => 'error']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filtroEstado', 'error')
        ->has('importaciones.data', 1)
        ->where('importaciones.data.0.id', $error->id)
    );
});

test('el filtro de estado por defecto se combina con el término de búsqueda', function () {
    TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);
    $enProgreso = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now()->subMinute(),
        'estado' => 'en_progreso',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.index', ['q' => 'importar_pendientes']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('importaciones.data', 1)
        ->where('importaciones.data.0.id', $enProgreso->id)
    );
});

test('el detalle de una importación incluye los snapshots que produjo', function () {
    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'en_progreso',
    ]);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-importacion-1',
        'payload_crudo' => ['estado' => 'EN_TRAMITE'],
        'payload_normalizado' => ['estado' => 'EN_TRAMITE'],
        'hash' => 'hash-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('sgf/importaciones/show')
        ->has('importacion.snapshots', 1)
        ->where('importacion.snapshots.0.referencia_externa', 'sgf-importacion-1')
        ->where('importacion.snapshots.0.monto', null)
        ->where('importacion.snapshots.0.folio_egreso', null)
        ->where('importacion.snapshots.0.caso_id', null)
        ->where('importacion.snapshots.0.caso_estado', null)
    );
});

test('el detalle de un snapshot con payload normalizado completo incluye sus datos financieros', function () {
    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-completo-1',
        'payload_crudo' => [],
        'payload_normalizado' => [
            'rut' => '11.111.111-1',
            'monto' => 250000.0,
            'estado' => 'EN_TRAMITE',
            'folio_egreso' => 'EGR-100',
            'numero' => '324',
            'periodo' => '2026-07',
            'fecha_sii' => '2026-07-01',
            'observacion' => 'Sin observaciones',
        ],
        'hash' => 'hash-completo-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('importacion.snapshots.0.rut', '11.111.111-1')
        ->where('importacion.snapshots.0.monto', 250000)
        ->where('importacion.snapshots.0.estado_sgf', 'EN_TRAMITE')
        ->where('importacion.snapshots.0.folio_egreso', 'EGR-100')
        ->where('importacion.snapshots.0.numero', '324')
        ->where('importacion.snapshots.0.periodo', '2026-07')
        ->where('importacion.snapshots.0.fecha_sii', '2026-07-01')
        ->where('importacion.snapshots.0.observacion', 'Sin observaciones')
    );
});

test('un snapshot ya importado enlaza a su caso de pago y muestra el nombre del proveedor identificado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    $proveedor = Proveedor::create([
        'rutproveedor' => '11111111-1',
        'nombre' => 'Proveedor Identificado SPA',
        'activo' => true,
    ]);
    $caso = crearCasoPagoProveedorParaImportacion('sgf-enlazado-1', '11.111.111-1', $proveedor->id);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-enlazado-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '11.111.111-1', 'monto' => 100000, 'estado' => 'EN_TRAMITE'],
        'hash' => 'hash-enlazado-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('importacion.snapshots.0.caso_id', $caso->id)
        ->where('importacion.snapshots.0.caso_estado', $caso->proceso->estadoActual->codigo)
        ->where('importacion.snapshots.0.proveedor', 'Proveedor Identificado SPA')
    );
});

test('un snapshot sin caso de pago asociado no incluye enlace y muestra el RUT como proveedor', function () {
    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-sin-caso-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '22.222.222-2', 'monto' => 50000, 'estado' => 'EN_TRAMITE'],
        'hash' => 'hash-sin-caso-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('importacion.snapshots.0.caso_id', null)
        ->where('importacion.snapshots.0.caso_estado', null)
        ->where('importacion.snapshots.0.proveedor', '22.222.222-2')
    );
});

test('el resumen agregado calcula el monto total y los proveedores identificados vs. no identificados', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    $proveedor = Proveedor::create([
        'rutproveedor' => '11111111-1',
        'nombre' => 'Proveedor Identificado SPA',
        'activo' => true,
    ]);
    crearCasoPagoProveedorParaImportacion('sgf-resumen-1', '11.111.111-1', $proveedor->id);
    crearCasoPagoProveedorParaImportacion('sgf-resumen-2', '33.333.333-3', null);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-resumen-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '11.111.111-1', 'monto' => 100000],
        'hash' => 'hash-resumen-1',
        'capturado_en' => now(),
    ]);
    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-resumen-2',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '33.333.333-3', 'monto' => 50000],
        'hash' => 'hash-resumen-2',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('importacion.resumen.monto_total', 150000)
        ->where('importacion.resumen.proveedores_identificados', 1)
        ->where('importacion.resumen.proveedores_no_identificados', 1)
    );
});

test('un caso con tipo de proceso, Traspaso, checklist obligatorio completo y proveedor identificado se marca listo_para_egreso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(TiposProcesoPagoSeeder::class);
    $this->seed(RequisitosDocumentalesPagoProveedoresSeeder::class);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    $proveedor = Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Proveedor Listo SPA', 'activo' => true]);
    $caso = crearCasoPagoProveedorParaImportacion('sgf-listo-1', '11.111.111-1', $proveedor->id);
    dejarCasoListoParaEgreso($caso);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-listo-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '11.111.111-1', 'monto' => 100000],
        'hash' => 'hash-listo-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('importacion.snapshots.0.listo_para_egreso', true)
    );
});

test('a un caso le falta el tipo de proceso clasificado y se marca no listo_para_egreso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(TiposProcesoPagoSeeder::class);
    $this->seed(RequisitosDocumentalesPagoProveedoresSeeder::class);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    $proveedor = Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Proveedor Sin Tipo SPA', 'activo' => true]);
    $caso = crearCasoPagoProveedorParaImportacion('sgf-sin-tipo-1', '11.111.111-1', $proveedor->id);
    dejarCasoListoParaEgreso($caso, conTipoProceso: false);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-sin-tipo-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '11.111.111-1', 'monto' => 100000],
        'hash' => 'hash-sin-tipo-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('importacion.snapshots.0.listo_para_egreso', false)
    );
});

test('a un caso le falta el Traspaso registrado y se marca no listo_para_egreso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(TiposProcesoPagoSeeder::class);
    $this->seed(RequisitosDocumentalesPagoProveedoresSeeder::class);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    $proveedor = Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Proveedor Sin Traspaso SPA', 'activo' => true]);
    $caso = crearCasoPagoProveedorParaImportacion('sgf-sin-traspaso-1', '11.111.111-1', $proveedor->id);
    dejarCasoListoParaEgreso($caso, conTraspaso: false);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-sin-traspaso-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '11.111.111-1', 'monto' => 100000],
        'hash' => 'hash-sin-traspaso-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('importacion.snapshots.0.listo_para_egreso', false)
    );
});

test('a un caso le falta completar el checklist obligatorio y se marca no listo_para_egreso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(TiposProcesoPagoSeeder::class);
    $this->seed(RequisitosDocumentalesPagoProveedoresSeeder::class);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    $proveedor = Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Proveedor Sin Checklist SPA', 'activo' => true]);
    $caso = crearCasoPagoProveedorParaImportacion('sgf-sin-checklist-1', '11.111.111-1', $proveedor->id);
    dejarCasoListoParaEgreso($caso, conChecklistCompleto: false);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-sin-checklist-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '11.111.111-1', 'monto' => 100000],
        'hash' => 'hash-sin-checklist-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('importacion.snapshots.0.listo_para_egreso', false)
    );
});

test('a un caso le falta el proveedor identificado y se marca no listo_para_egreso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(TiposProcesoPagoSeeder::class);
    $this->seed(RequisitosDocumentalesPagoProveedoresSeeder::class);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    $caso = crearCasoPagoProveedorParaImportacion('sgf-sin-proveedor-1', '11.111.111-1', null);
    dejarCasoListoParaEgreso($caso);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-sin-proveedor-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '11.111.111-1', 'monto' => 100000],
        'hash' => 'hash-sin-proveedor-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('importacion.snapshots.0.listo_para_egreso', false)
    );
});

test('el resumen de la corrida cuenta correctamente los casos listos y pendientes para Egreso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(TiposProcesoPagoSeeder::class);
    $this->seed(RequisitosDocumentalesPagoProveedoresSeeder::class);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    $proveedor = Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Proveedor Resumen Listo SPA', 'activo' => true]);
    $casoListo = crearCasoPagoProveedorParaImportacion('sgf-resumen-listo-1', '11.111.111-1', $proveedor->id);
    dejarCasoListoParaEgreso($casoListo);

    $casoPendiente = crearCasoPagoProveedorParaImportacion('sgf-resumen-pendiente-1', '33.333.333-3', null);
    dejarCasoListoParaEgreso($casoPendiente, conTraspaso: false);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-resumen-listo-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '11.111.111-1', 'monto' => 100000],
        'hash' => 'hash-resumen-listo-1',
        'capturado_en' => now(),
    ]);
    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-resumen-pendiente-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '33.333.333-3', 'monto' => 50000],
        'hash' => 'hash-resumen-pendiente-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('importacion.resumen.casos_listos', 1)
        ->where('importacion.resumen.casos_pendientes', 1)
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('sgf.importaciones.index'));

    $response->assertRedirect(route('login'));
});
