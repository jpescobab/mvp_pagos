<?php

namespace App\Http\Controllers;

use App\Models\CasoPagoProveedor;
use App\Models\EgresoCgu;
use App\Models\EjecucionInformeRazonado;
use App\Models\ProcesoAdquisicion;
use App\Services\Indicadores\IndicadorEconomicoSelector;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(IndicadorEconomicoSelector $indicadores): Response
    {
        $hoy = Date::today();

        $kpis = [
            'casos_pago_activos' => CasoPagoProveedor::whereHas(
                'proceso',
                fn ($query) => $query->whereNull('cerrado_en'),
            )->count(),
            'egresos_cgu_mes' => EgresoCgu::whereYear('fecha', $hoy->year)
                ->whereMonth('fecha', $hoy->month)
                ->count(),
            'adquisiciones_activas' => ProcesoAdquisicion::whereHas(
                'proceso',
                fn ($query) => $query->whereNull('cerrado_en'),
            )->count(),
            'informes_en_curso' => EjecucionInformeRazonado::whereHas(
                'proceso',
                fn ($query) => $query->whereNull('cerrado_en'),
            )->count(),
        ];

        $casosRecientes = CasoPagoProveedor::with(['proveedor', 'proceso.estadoActual'])
            ->latest()
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (CasoPagoProveedor $caso): array => [
                'id' => $caso->id,
                'sgf_id' => $caso->sgf_id,
                'proveedor' => $caso->proveedor?->nombre,
                'monto' => $caso->monto !== null ? (string) $caso->monto : null,
                'estado' => $caso->proceso?->estadoActual?->nombre,
                'cerrado' => $caso->proceso?->cerrado_en !== null,
            ]);

        return Inertia::render('dashboard', [
            'kpis' => $kpis,
            'indicadores' => $indicadores->ultimosPorTipo(['UF', 'UTM', 'UTA', 'IPC', 'USD']),
            'casosRecientes' => $casosRecientes,
        ]);
    }
}
