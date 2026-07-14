<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Exceptions\ConectorAutomatizacionNoAutorizadoException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PagoProveedores\CasoPagoProveedorResource;
use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use App\Services\Sgf\ConectorSgfPlaywrightService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CasoPagoProveedorController extends Controller
{
    public function __construct(
        private readonly ResolutorChecklistDocumentalProceso $resolutorChecklist,
        private readonly ConectorSgfPlaywrightService $conectorSgf,
    ) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', CasoPagoProveedor::class);

        $casos = CasoPagoProveedor::with(['proveedor', 'proceso.estadoActual', 'proceso.definicionWorkflow.transiciones'])
            ->paginate(20);

        return Inertia::render('pago-proveedores/casos/index', [
            'casos' => CasoPagoProveedorResource::collection($casos),
        ]);
    }

    public function show(CasoPagoProveedor $caso, Request $request): Response
    {
        Gate::authorize('view', $caso);

        $this->cargarDetalle($caso, $request);

        return Inertia::render('pago-proveedores/casos/show', [
            'caso' => new CasoPagoProveedorResource($caso),
            'tiposDocumento' => TipoDocumento::where('activo', true)->get(['id', 'nombre']),
            'tiposProcesoPago' => TipoProcesoPago::where('activo', true)->get(['id', 'codigo', 'nombre']),
        ]);
    }

    public function verificarSgf(CasoPagoProveedor $caso): RedirectResponse
    {
        Gate::authorize('verificarCasoSgf', CasoPagoProveedor::class);

        try {
            $resultado = $this->conectorSgf->verificarCaso($caso->sgf_id);
        } catch (ConectorAutomatizacionNoAutorizadoException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('verificacionSgf', [
            'encontrada' => $resultado['encontrada'],
            'payload_crudo' => $resultado['snapshot']?->payload_crudo,
        ]);

        return to_route('pago-proveedores.casos.show', $caso);
    }

    private function cargarDetalle(CasoPagoProveedor $caso, Request $request): void
    {
        $caso->load([
            'proveedor',
            'proceso.estadoActual',
            'proceso.definicionWorkflow.transiciones',
            'proceso.historialTransiciones.transicion',
            'proceso.historialTransiciones.estadoOrigen',
            'proceso.historialTransiciones.estadoDestino',
            'proceso.historialTransiciones.user',
            'procesoAdquisicion',
            'registrosContablesCgu.registradoPor',
            'registrosPagoBancario.registradoPor',
            'snapshotsSgf',
            'egresoCguItems.egreso',
            'facturas',
        ]);

        $conjuntoRequisitos = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->first();

        if ($conjuntoRequisitos !== null) {
            $this->resolutorChecklist->resolve($caso->proceso, $conjuntoRequisitos, $request->user());
        }

        $caso->proceso->load([
            'checklist.items',
            'tipoProcesoPago',
            'vinculosDocumento.documento.tipoDocumento',
            'vinculosDocumento.documento.versiones',
            'vinculosDocumento.documento.validaciones.validadoPor',
        ]);
    }
}
