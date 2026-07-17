<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Exceptions\ConectorAutomatizacionNoAutorizadoException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PagoProveedores\CasoPagoProveedorResource;
use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use App\Services\PagoProveedores\ListadoCasoPagoProveedorService;
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
        private readonly ListadoCasoPagoProveedorService $listadoCasos,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CasoPagoProveedor::class);

        $estadoFiltro = $request->string('estado')->toString();
        $estadoFiltro = $estadoFiltro === '' ? null : $estadoFiltro;

        $casos = $this->listadoCasos->paginar($estadoFiltro);

        return Inertia::render('pago-proveedores/casos/index', [
            'casos' => CasoPagoProveedorResource::collection($casos),
            'estadosWorkflow' => DefinicionWorkflow::where('codigo', 'pago_proveedores')
                ->first()
                ?->estados()
                ->orderBy('id')
                ->get(['codigo', 'nombre']) ?? [],
            'filtroEstado' => $estadoFiltro,
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
