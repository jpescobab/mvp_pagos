<?php

namespace App\Services\Seguridad;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permisos compartidos al frontend (`auth.permissions`) para condicionar UI
 * en cada request Inertia. Cachea el resultado por usuario porque
 * getAllPermissions() hidrata un modelo Permission por cada permiso
 * efectivo del usuario en cada request; bajo CACHE_STORE=database el
 * closure SHALL retornar un array plano, nunca la Collection directamente
 * (config('cache.serializable_classes') es false, así que cachear un
 * objeto se corrompe al deserializarlo — ver DatabaseStore::unserialize()).
 */
class PermisosCompartidosResolver
{
    private const CACHE_TTL_MINUTOS = 5;

    /**
     * @return Collection<int, string>
     */
    public function paraUsuario(?User $user): Collection
    {
        if ($user === null) {
            return collect();
        }

        $nombres = Cache::remember(
            $this->cacheKey($user->id),
            now()->addMinutes(self::CACHE_TTL_MINUTOS),
            fn (): array => ($user->hasRole('superadmin')
                ? Permission::query()->orderBy('name')->pluck('name')
                : $user->getAllPermissions()->pluck('name'))->values()->all(),
        );

        return collect($nombres);
    }

    public function invalidarParaUsuario(int $usuarioId): void
    {
        Cache::forget($this->cacheKey($usuarioId));
    }

    public function invalidarParaRol(Role $rol): void
    {
        /** @var Collection<int, User> $usuarios */
        $usuarios = $rol->users;

        $usuarios->each(fn (User $usuario) => $this->invalidarParaUsuario($usuario->id));
    }

    private function cacheKey(int $usuarioId): string
    {
        return "seguridad:permisos_compartidos:{$usuarioId}";
    }
}
