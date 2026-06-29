<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Resources\PagoProveedores\CasoPagoProveedorResource;
use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\TipoDocumento;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CasoPagoProveedorController extends Controller
{
    public function __construct(private readonly ResolutorChecklistDocumentalProceso $resolutorChecklist) {}

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
            'snapshotsSgf.importacion.iniciadoPor',
            'egresoCguItems.egreso',
        ]);

        $conjuntoRequisitos = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->first();

        if ($conjuntoRequisitos !== null) {
            $this->resolutorChecklist->resolve($caso->proceso, $conjuntoRequisitos, $request->user());
        }

        $caso->proceso->load([
            'checklist.items',
            'vinculosDocumento.documento.tipoDocumento',
            'vinculosDocumento.documento.versiones',
            'vinculosDocumento.documento.validaciones.validadoPor',
        ]);

        return Inertia::render('pago-proveedores/casos/show', [
            'caso' => new CasoPagoProveedorResource($caso),
            'tiposDocumento' => TipoDocumento::where('activo', true)->get(['id', 'nombre']),
        ]);
    }
}
