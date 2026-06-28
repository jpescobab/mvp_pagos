<?php

namespace App\Http\Controllers\Indicadores;

use App\Http\Controllers\Controller;
use App\Http\Resources\Indicadores\IndicadorEconomicoResource;
use App\Models\IndicadorEconomico;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndicadorEconomicoController extends Controller
{
    private const TIPOS_VALIDOS = ['UF', 'USD', 'UTM', 'UTA', 'IPC'];

    public function index(Request $request): Response
    {
        $tipo = $request->string('tipo')->toString();

        $indicadores = IndicadorEconomico::query()
            ->when(
                in_array($tipo, self::TIPOS_VALIDOS, true),
                fn ($query) => $query->where('tipo', $tipo),
            )
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('indicadores-economicos/index', [
            'indicadores' => IndicadorEconomicoResource::collection($indicadores),
            'tipo' => $tipo !== '' ? $tipo : null,
        ]);
    }
}
