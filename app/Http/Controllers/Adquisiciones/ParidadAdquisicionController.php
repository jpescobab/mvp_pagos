<?php

namespace App\Http\Controllers\Adquisiciones;

use App\Http\Controllers\Controller;
use App\Models\ProcesoAdquisicion;
use App\Services\Indicadores\IndicadorEconomicoSelector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class ParidadAdquisicionController extends Controller
{
    /**
     * Previsualiza la paridad (UF/USD) para una fecha, sin persistir nada —
     * el mismo `IndicadorEconomicoSelector` que resuelve `crear()`/`actualizar()`
     * en el servidor. Solo para feedback en vivo del formulario; el valor
     * realmente comprometido se vuelve a resolver server-side al guardar.
     */
    public function show(Request $request, IndicadorEconomicoSelector $selector): JsonResponse
    {
        Gate::authorize('create', ProcesoAdquisicion::class);

        $request->validate([
            'moneda' => ['required', 'in:UF,USD'],
            'fecha' => ['required', 'date'],
        ]);

        $fecha = Carbon::parse($request->string('fecha')->toString());
        $indicador = $selector->paraFecha($request->string('moneda')->toString(), $fecha);

        if ($indicador === null) {
            return response()->json(['mensaje' => 'Sin valor registrado para esa fecha.'], 404);
        }

        return response()->json([
            'valor' => (float) $indicador->valor,
            'fecha_valor' => $indicador->fecha_valor,
        ]);
    }
}
