<?php

namespace App\Services\Sgf;

use App\Exceptions\ConectorAutomatizacionNoAutorizadoException;
use App\Models\ConectorAutomatizacionNavegador;
use App\Models\Documento;
use App\Models\EjecucionAutomatizacionNavegador;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TipoDocumento;
use App\Models\TrabajoIntegracion;
use App\Services\Integraciones\AutomatizacionNavegadorService;
use App\Services\Integraciones\IntegracionExternaService;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Illuminate\Support\Facades\Http;
use Throwable;

class ConectorSgfPlaywrightService
{
    private const CODIGO_CONECTOR = 'SGF_PLAYWRIGHT';

    public function __construct(
        private readonly IntegracionExternaService $integracionExterna,
        private readonly AutomatizacionNavegadorService $automatizacionNavegador,
        private readonly NormalizadorSgf $normalizadorSgf,
        private readonly CasoPagoProveedorImporter $casoPagoProveedorImporter,
    ) {}

    /**
     * Verifica que el conector Playwright de SGF esté activo y autorizado.
     * Debe llamarse antes de crear cualquier `trabajo_integracion` (síncrono o
     * antes de encolar el Job de importación masiva), para no dejar un
     * `trabajo_integracion` huérfano cuando el conector no está autorizado.
     */
    public function verificarConectorAutorizado(): void
    {
        $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
        $this->conectorAutorizado($sistema);
    }

    /**
     * @return array{encontrada: bool, snapshot: SnapshotDatosExterno|null}
     */
    public function verificarCaso(string $sgfId): array
    {
        $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
        $conector = $this->conectorAutorizado($sistema);

        $trabajo = $this->integracionExterna->iniciarTrabajo($sistema, 'verificar_caso', 'playwright');
        $ejecucion = $this->automatizacionNavegador->iniciarEjecucion($conector, trabajo: $trabajo);

        $respuesta = $this->llamarMicroservicio('casos/verificar', ['sgf_id' => $sgfId], timeout: 120, trabajo: $trabajo, ejecucion: $ejecucion);

        if ($respuesta === null) {
            return ['encontrada' => false, 'snapshot' => null];
        }

        $this->registrarPasos($ejecucion, $respuesta['pasos'] ?? []);

        if (! ($respuesta['encontrada'] ?? false)) {
            $this->automatizacionNavegador->finalizarEjecucion($ejecucion, 'completado');
            $this->integracionExterna->finalizarTrabajo($trabajo, 'completado');

            return ['encontrada' => false, 'snapshot' => null];
        }

        $payloadCrudo = $respuesta['payload_crudo'] ?? [];
        $snapshot = $this->registrarSnapshot($sistema, $sgfId, $payloadCrudo, $trabajo);
        $trabajo->increment('total_elementos');

        $this->automatizacionNavegador->finalizarEjecucion($ejecucion, 'completado');
        $this->integracionExterna->finalizarTrabajo($trabajo, 'completado');

        return ['encontrada' => true, 'snapshot' => $snapshot];
    }

    public function importarPendientes(TrabajoIntegracion $trabajo): void
    {
        $sistema = $trabajo->sistemaExterno;
        $conector = $this->conectorAutorizado($sistema);

        $ejecucion = $this->automatizacionNavegador->iniciarEjecucion($conector, trabajo: $trabajo);

        $respuesta = $this->llamarMicroservicio('casos/importar-pendientes', [], timeout: 600, trabajo: $trabajo, ejecucion: $ejecucion);

        if ($respuesta === null) {
            return;
        }

        $this->registrarPasos($ejecucion, $respuesta['pasos'] ?? []);

        foreach ($respuesta['filas'] ?? [] as $fila) {
            $this->registrarSnapshot($sistema, (string) $fila['sgf_id'], $fila['payload_crudo'] ?? [], $trabajo);
            $trabajo->increment('total_elementos');
        }

        $this->automatizacionNavegador->finalizarEjecucion($ejecucion, 'completado');
        $this->integracionExterna->finalizarTrabajo($trabajo, 'completado');
    }

    private function conectorAutorizado(SistemaExterno $sistema): ConectorAutomatizacionNavegador
    {
        $conector = ConectorAutomatizacionNavegador::where('sistema_externo_id', $sistema->id)
            ->where('codigo', self::CODIGO_CONECTOR)
            ->firstOrFail();

        if (! $conector->estaAutorizado()) {
            throw ConectorAutomatizacionNoAutorizadoException::paraConector($conector);
        }

        return $conector;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|null
     */
    private function llamarMicroservicio(string $ruta, array $body, int $timeout, TrabajoIntegracion $trabajo, EjecucionAutomatizacionNavegador $ejecucion): ?array
    {
        $endpoint = rtrim((string) config('services.sgf_playwright.base_url'), '/').'/'.$ruta;

        try {
            $respuesta = Http::withHeader('X-Api-Key', (string) config('services.sgf_playwright.api_key'))
                ->timeout($timeout)
                ->post($endpoint, $body);
        } catch (Throwable $e) {
            $this->finalizarConError($trabajo, $ejecucion, $e->getMessage());

            return null;
        }

        if (! $respuesta->successful()) {
            $mensaje = (string) ($respuesta->json('error') ?? "El conector Playwright de SGF respondió con estado {$respuesta->status()}");
            $this->finalizarConError($trabajo, $ejecucion, $mensaje);

            return null;
        }

        return $respuesta->json() ?? [];
    }

    private function finalizarConError(TrabajoIntegracion $trabajo, EjecucionAutomatizacionNavegador $ejecucion, string $mensaje): void
    {
        $this->automatizacionNavegador->finalizarEjecucion($ejecucion, 'error', error: $mensaje);
        $this->integracionExterna->finalizarTrabajo($trabajo, 'error', $mensaje);
    }

    /**
     * @param  list<array<string, mixed>>  $pasos
     */
    private function registrarPasos(EjecucionAutomatizacionNavegador $ejecucion, array $pasos): void
    {
        foreach ($pasos as $paso) {
            $this->automatizacionNavegador->registrarPaso(
                $ejecucion,
                (int) ($paso['orden'] ?? 0),
                (string) ($paso['accion'] ?? ''),
                (string) ($paso['estado'] ?? 'completado'),
                $paso['detalle'] ?? null,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payloadCrudo
     */
    private function registrarSnapshot(SistemaExterno $sistema, string $sgfId, array $payloadCrudo, TrabajoIntegracion $trabajo): SnapshotDatosExterno
    {
        $snapshot = $this->integracionExterna->registrarSnapshot(
            sistema: $sistema,
            metodoCaptura: 'playwright',
            payloadCrudo: $payloadCrudo,
            payloadNormalizado: $this->normalizadorSgf->normalizar($payloadCrudo),
            referenciaExterna: $sgfId,
            trabajo: $trabajo,
        );

        foreach ($payloadCrudo['documentos'] ?? [] as $documentoSgf) {
            $this->vincularDocumento($snapshot, $documentoSgf);
        }

        $this->casoPagoProveedorImporter->importarDesdeSnapshot($snapshot);

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $documentoSgf
     */
    private function vincularDocumento(SnapshotDatosExterno $snapshot, array $documentoSgf): void
    {
        $tipo = TipoDocumento::firstOrCreate(
            ['codigo' => $documentoSgf['tipo_documento_codigo']],
            ['nombre' => $documentoSgf['tipo_documento_codigo']],
        );

        $documento = Documento::create(['tipo_documento_id' => $tipo->id]);

        $documento->versiones()->create([
            'numero_version' => 1,
            'ruta_archivo' => $documentoSgf['ruta_archivo'],
            'nombre_archivo' => $documentoSgf['nombre_archivo'],
        ]);

        $snapshot->documentos()->create(['documento_id' => $documento->id]);
    }
}
