<?php

namespace App\Http\Controllers\Sgf;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sgf\ImportacionSgfResource;
use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\SistemaExterno;
use App\Models\TrabajoIntegracion;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use App\Services\Sgf\ImportacionesSgfPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportacionSgfController extends Controller
{
    /**
     * Estados de un trabajo que "aún requieren atención" (todo lo que no es
     * completado): el filtro "no completadas" replica la vista anterior.
     *
     * @var list<string>
     */
    private const ESTADOS_NO_COMPLETADAS = ['en_progreso', 'error', 'huerfano'];

    public function __construct(
        private readonly ResolutorChecklistDocumentalProceso $resolutorChecklist,
        private readonly ImportacionesSgfPresenter $importacionesPresenter,
    ) {}

    public function index(Request $request): Response
    {
        $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
        $q = $request->string('q')->trim()->toString();
        $estado = $request->string('estado')->trim()->toString();
        $estado = $estado !== '' ? $estado : null;

        $importaciones = TrabajoIntegracion::where('sistema_externo_id', $sistema->id)
            ->with('iniciadoPor')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('tipo', 'like', "%{$q}%")
                    ->orWhereHas('iniciadoPor', fn ($usuario) => $usuario->where('name', 'like', "%{$q}%"))
            ))
            // Por defecto (sin filtro explícito) el listado muestra solo las
            // corridas completadas, que son las que normalmente se consultan.
            ->when($estado === null, fn ($query) => $query->where('estado', 'completado'))
            ->when($estado === 'no_completadas', fn ($query) => $query->whereIn('estado', self::ESTADOS_NO_COMPLETADAS))
            ->when(
                $estado !== null && $estado !== 'todos' && $estado !== 'no_completadas',
                fn ($query) => $query->where('estado', $estado),
            )
            ->latest('iniciado_en')
            ->paginate(20)
            ->withQueryString();

        $contexto = $this->importacionesPresenter->contextoListado($importaciones->getCollection());

        $importaciones->getCollection()->each(function (TrabajoIntegracion $trabajo) use ($contexto): void {
            $trabajo->desgloseEstados = $contexto['desglosePorTrabajo'][$trabajo->id] ?? [];
            $trabajo->eliminable = $contexto['eliminablePorTrabajo'][$trabajo->id] ?? false;
        });

        return Inertia::render('sgf/importaciones/index', [
            'importaciones' => ImportacionSgfResource::collection($importaciones),
            'q' => $q !== '' ? $q : null,
            'filtroEstado' => $estado,
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
