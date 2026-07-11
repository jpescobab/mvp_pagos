<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Indicadores\IndicadorEconomicoSelector;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;
use Spatie\Permission\Models\Permission;

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
                'permissions' => $this->permisosCompartidos($request->user()),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'appearance' => $request->cookie('appearance') ?? 'system',
            'indicadoresTopbar' => $request->user()
                ? app(IndicadorEconomicoSelector::class)->ultimosPorTipo(['UF', 'UTM', 'USD', 'IPC'])
                : [],
        ];
    }

    /**
     * Permisos que se comparten con el frontend para condicionar la UI. El
     * superadmin bypassea todos los gates (Gate::before), por lo que su lista
     * SHALL reflejar acceso total; el resto recibe sus permisos efectivos.
     *
     * @return Collection<int, string>
     */
    private function permisosCompartidos(?User $user): Collection
    {
        if ($user === null) {
            return collect();
        }

        if ($user->hasRole('superadmin')) {
            return Permission::query()->orderBy('name')->pluck('name');
        }

        return $user->getAllPermissions()->pluck('name');
    }
}
