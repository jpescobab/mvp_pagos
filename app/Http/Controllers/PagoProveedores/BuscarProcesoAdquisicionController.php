<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Resources\PagoProveedores\ProcesoAdquisicionResumenResource;
use App\Models\CasoPagoProveedor;
use App\Models\ProcesoAdquisicion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BuscarProcesoAdquisicionController extends Controller
{
    private const MAXIMO_RESULTADOS = 10;

    public function __invoke(CasoPagoProveedor $caso, Request $request): AnonymousResourceCollection
    {
        Gate::authorize('vincularAdquisicion', $caso);

        $termino = $request->string('q')->toString();

        $procesos = ProcesoAdquisicion::with('proveedor')
            ->when($termino !== '', function ($query) use ($termino) {
                $query->where(function ($query) use ($termino) {
                    $query->where('codigo', 'like', "%{$termino}%")
                        ->orWhere('objeto', 'like', "%{$termino}%")
                        ->orWhere('monto', 'like', "%{$termino}%")
                        ->orWhereHas('proveedor', function ($query) use ($termino) {
                            $query->where('nombre', 'like', "%{$termino}%");
                        });
                });
            })
            ->limit(self::MAXIMO_RESULTADOS)
            ->get();

        return ProcesoAdquisicionResumenResource::collection($procesos);
    }
}
