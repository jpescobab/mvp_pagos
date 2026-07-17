<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Exceptions\TransicionWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\CrearEgresoCguRequest;
use App\Http\Resources\PagoProveedores\EgresoCguResource;
use App\Models\EgresoCgu;
use App\Services\PagoProveedores\CasosElegiblesEgresoCguService;
use App\Services\PagoProveedores\EgresoCguCreador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EgresoCguController extends Controller
{
    public function __construct(
        private readonly CasosElegiblesEgresoCguService $casosElegibles,
        private readonly EgresoCguCreador $egresoCguCreador,
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
        $casoPagoProveedorId = $request->integer('caso_pago_proveedor_id') ?: null;

        return Inertia::render('pago-proveedores/egresos-cgu/crear', [
            'casos' => $this->casosElegibles->paraFormulario($trabajoIntegracionId),
            'trabajoIntegracionId' => $trabajoIntegracionId,
            'casoPagoProveedorId' => $casoPagoProveedorId,
        ]);
    }

    public function store(CrearEgresoCguRequest $request): RedirectResponse
    {
        try {
            $this->egresoCguCreador->crear($request->validated(), $request->user());
        } catch (TransicionWorkflowException $e) {
            return back()->withErrors(['casos' => $e->getMessage()]);
        }

        return to_route('pago-proveedores.egresos-cgu.index');
    }
}
