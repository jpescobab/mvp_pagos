<?php

namespace App\Http\Controllers\Sgf;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sgf\ImportacionSgfResource;
use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\SistemaExterno;
use App\Models\TrabajoIntegracion;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportacionSgfController extends Controller
{
    public function __construct(
        private readonly ResolutorChecklistDocumentalProceso $resolutorChecklist,
    ) {}

    public function index(Request $request): Response
    {
        $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
        $q = $request->string('q')->trim()->toString();

        $importaciones = TrabajoIntegracion::where('sistema_externo_id', $sistema->id)
            ->with('iniciadoPor')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('tipo', 'like', "%{$q}%")
                    ->orWhereHas('iniciadoPor', fn ($usuario) => $usuario->where('name', 'like', "%{$q}%"))
            ))
            ->latest('iniciado_en')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('sgf/importaciones/index', [
            'importaciones' => ImportacionSgfResource::collection($importaciones),
            'q' => $q !== '' ? $q : null,
        ]);
    }

    public function show(TrabajoIntegracion $trabajoIntegracion): Response
    {
        $trabajoIntegracion->load(['iniciadoPor', 'snapshotsDatosExternos']);

        $sgfIds = $trabajoIntegracion->snapshotsDatosExternos->pluck('referencia_externa')->unique();

        $casosPorSgfId = CasoPagoProveedor::whereIn('sgf_id', $sgfIds)
            ->with([
                'proveedor',
                'proceso.estadoActual',
                'proceso.checklist.items',
                'proceso.tipoProcesoPago',
                'registrosContablesCgu',
            ])
            ->get()
            ->keyBy('sgf_id');

        $conjuntoRequisitos = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->first();

        if ($conjuntoRequisitos !== null) {
            foreach ($casosPorSgfId as $caso) {
                if ($caso->proceso !== null) {
                    $this->resolutorChecklist->resolve($caso->proceso, $conjuntoRequisitos);
                    $caso->proceso->load('checklist.items');
                }
            }
        }

        return Inertia::render('sgf/importaciones/show', [
            'importacion' => (new ImportacionSgfResource($trabajoIntegracion))->withCasos($casosPorSgfId),
        ]);
    }
}
