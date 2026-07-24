<?php

namespace App\Http\Middleware;

use App\Services\Indicadores\IndicadorEconomicoSelector;
use App\Services\Seguridad\PermisosCompartidosResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'permissions' => app(PermisosCompartidosResolver::class)->paraUsuario($request->user()),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'appearance' => $request->cookie('appearance') ?? 'system',
            'indicadoresTopbar' => $request->user()
                ? app(IndicadorEconomicoSelector::class)->ultimosPorTipo(['UF', 'UTM', 'USD', 'IPC'])
                : [],
            // Solo el conteo para el badge de la campana; la lista se pide al
            // endpoint al abrir el panel. Sin caché: debe reflejar de inmediato
            // el marcado como leídas y las transiciones nuevas.
            'notificaciones_no_leidas' => $request->user()?->unreadNotifications()->count() ?? 0,
        ];
    }
}
