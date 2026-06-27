<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\CrearEgresoCguRequest;
use App\Http\Resources\PagoProveedores\EgresoCguResource;
use App\Models\CasoPagoProveedor;
use App\Models\EgresoCgu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EgresoCguController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', EgresoCgu::class);

        $egresos = EgresoCgu::with('items.caso')->paginate(20);

        return Inertia::render('pago-proveedores/egresos-cgu/index', [
            'egresos' => EgresoCguResource::collection($egresos),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', EgresoCgu::class);

        $casos = CasoPagoProveedor::with('proveedor')->get()->map(fn (CasoPagoProveedor $caso) => [
            'id' => $caso->id,
            'sgf_id' => $caso->sgf_id,
            'proveedor' => ['nombre' => $caso->proveedor?->nombre],
            'monto' => $caso->monto,
        ]);

        return Inertia::render('pago-proveedores/egresos-cgu/crear', [
            'casos' => $casos,
        ]);
    }

    public function store(CrearEgresoCguRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos) {
            $egreso = EgresoCgu::create([
                'numero_egreso' => $datos['numero_egreso'],
                'fecha' => $datos['fecha'],
                'observaciones' => $datos['observaciones'] ?? null,
                'monto_total' => array_sum(array_column($datos['casos'], 'monto')),
            ]);

            foreach ($datos['casos'] as $item) {
                $egreso->items()->create([
                    'caso_pago_proveedor_id' => $item['caso_pago_proveedor_id'],
                    'monto' => $item['monto'],
                ]);
            }
        });

        return to_route('pago-proveedores.egresos-cgu.index');
    }
}
