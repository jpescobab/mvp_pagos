<?php

namespace App\Http\Controllers\Indicadores;

use App\Http\Controllers\Controller;
use App\Http\Resources\Indicadores\IndicadorEconomicoResource;
use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Services\Indicadores\ServicioImportacionIndicadores;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IndicadorEconomicoController extends Controller
{
    private const CODIGOS_VALIDOS = ['UF', 'USD', 'UTM', 'UTA', 'IPC'];

    public function index(Request $request): Response
    {
        $codigo = $request->string('codigo')->toString();

        $indicadores = IndicadorEconomico::query()
            ->when(
                in_array($codigo, self::CODIGOS_VALIDOS, true),
                fn ($query) => $query->where('codigo', $codigo),
            )
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('indicadores-economicos/index', [
            'indicadores' => IndicadorEconomicoResource::collection($indicadores),
            'codigo' => $codigo !== '' ? $codigo : null,
        ]);
    }

    public function importarMensual(ServicioImportacionIndicadores $servicio): RedirectResponse
    {
        Gate::authorize('importar', IndicadorEconomicoImportacion::class);

        $servicio->importarMensual('manual');

        return back();
    }
}
