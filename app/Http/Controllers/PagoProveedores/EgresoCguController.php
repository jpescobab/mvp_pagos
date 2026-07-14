<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Exceptions\TransicionWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\CrearEgresoCguRequest;
use App\Http\Resources\PagoProveedores\EgresoCguResource;
use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\EgresoCgu;
use App\Models\TrabajoIntegracion;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use App\Services\PagoProveedores\ListoParaEgresoResolver;
use App\Services\PagoProveedores\RevisionEgresoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EgresoCguController extends Controller
{
    public function __construct(
        private readonly RevisionEgresoService $revisionEgreso,
        private readonly ResolutorChecklistDocumentalProceso $resolutorChecklist,
    ) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', EgresoCgu::class);

        $egresos = EgresoCgu::with('items.caso')->paginate(20);

        return Inertia::render('pago-proveedores/egresos-cgu/index', [
            'egresos' => EgresoCguResource::collection($egresos),
        ]);
    }

    public function show(EgresoCgu $egresoCgu): Response
    {
        Gate::authorize('view', $egresoCgu);

        $egresoCgu->load('items.caso');

        return Inertia::render('pago-proveedores/egresos-cgu/show', [
            'egreso' => new EgresoCguResource($egresoCgu),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', EgresoCgu::class);

        $trabajoIntegracionId = $request->integer('trabajo_integracion_id') ?: null;

        $query = CasoPagoProveedor::whereDoesntHave('egresoCguItems')->with([
            'proveedor',
            'proceso.checklist.items',
            'proceso.tipoProcesoPago',
            'registrosContablesCgu',
        ]);

        if ($trabajoIntegracionId !== null) {
            $trabajoIntegracion = TrabajoIntegracion::with('snapshotsDatosExternos')->find($trabajoIntegracionId);
            $sgfIds = $trabajoIntegracion?->snapshotsDatosExternos->pluck('referencia_externa')->unique() ?? collect();

            $query->whereIn('sgf_id', $sgfIds);
        }

        $conjuntoRequisitos = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->first();

        $casos = $query->get()->map(function (CasoPagoProveedor $caso) use ($conjuntoRequisitos) {
            if ($conjuntoRequisitos !== null && $caso->proceso !== null) {
                $this->resolutorChecklist->resolve($caso->proceso, $conjuntoRequisitos);
                $caso->proceso->load('checklist.items');
            }

            return [
                'id' => $caso->id,
                'sgf_id' => $caso->sgf_id,
                'proveedor' => ['nombre' => $caso->proveedor?->nombre],
                'monto' => $caso->monto,
                'listo' => app(ListoParaEgresoResolver::class)->resuelve($caso),
            ];
        });

        return Inertia::render('pago-proveedores/egresos-cgu/crear', [
            'casos' => $casos,
            'trabajoIntegracionId' => $trabajoIntegracionId,
        ]);
    }

    public function store(CrearEgresoCguRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        try {
            DB::transaction(function () use ($datos, $request) {
                $egreso = EgresoCgu::create([
                    'numero_egreso' => $datos['numero_egreso'],
                    'fecha' => $datos['fecha'],
                    'observaciones' => $datos['observaciones'] ?? null,
                    'monto_total' => array_sum(array_column($datos['casos'], 'monto')),
                ]);

                $casos = CasoPagoProveedor::with('proceso.estadoActual')
                    ->whereIn('id', array_column($datos['casos'], 'caso_pago_proveedor_id'))
                    ->get()
                    ->keyBy('id');

                foreach ($datos['casos'] as $item) {
                    $caso = $casos[$item['caso_pago_proveedor_id']];

                    $egreso->items()->create([
                        'caso_pago_proveedor_id' => $item['caso_pago_proveedor_id'],
                        'monto' => $item['monto'],
                    ]);

                    $egreso->actualizarCfinancieroSiFalta($caso);

                    // Recién asignado a un Egreso, el caso queda agrupado y
                    // pasa a la instancia de revisión de Finanzas.
                    $this->revisionEgreso->iniciarRevision($caso, $request->user());
                }
            });
        } catch (TransicionWorkflowException $e) {
            return back()->withErrors(['casos' => $e->getMessage()]);
        }

        return to_route('pago-proveedores.egresos-cgu.index');
    }
}
