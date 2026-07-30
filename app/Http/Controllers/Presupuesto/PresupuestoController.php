<?php

namespace App\Http\Controllers\Presupuesto;

use App\Http\Controllers\Controller;
use App\Http\Resources\Presupuesto\PresupuestoResource;
use App\Models\Presupuesto\Presupuesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PresupuestoController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Presupuesto::class);

        $anio = $request->integer('anio') ?: null;
        $cfinancieroId = $request->integer('cfinanciero_id') ?: null;

        $filtros = fn ($query) => $query
            ->when($anio, fn ($query) => $query->where('anio', $anio))
            ->when($cfinancieroId, fn ($query) => $query->where('cfinanciero_id', $cfinancieroId));

        $totalAsignado = (float) $filtros(Presupuesto::query())->sum('monto_asignado');

        $presupuestos = $filtros(Presupuesto::query())
            ->with(['cfinanciero', 'catalogo', 'planTarea'])
            ->join('catalogos', 'catalogos.id', '=', 'presupuestos.catalogo_id')
            ->select('presupuestos.*')
            ->orderBy('catalogos.codigo')
            ->orderByDesc('presupuestos.anio')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('presupuesto/lineas/index', [
            'presupuestos' => PresupuestoResource::collection($presupuestos),
            'anio' => $anio,
            'cfinanciero_id' => $cfinancieroId,
            'total_asignado' => $totalAsignado,
        ]);
    }
}
