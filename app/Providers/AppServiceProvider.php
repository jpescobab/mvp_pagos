<?php

namespace App\Providers;

use App\Models\CasoPagoProveedor;
use App\Models\EgresoCgu;
use App\Models\ProcesoAdquisicion;
use App\Models\User;
use App\Policies\CasoPagoProveedorPolicy;
use App\Policies\EgresoCguPolicy;
use App\Policies\ProcesoAdquisicionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        JsonResource::withoutWrapping();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Give superadmin unconditional access and audit every authorization denial.
     */
    protected function configureAuthorization(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(CasoPagoProveedor::class, CasoPagoProveedorPolicy::class);
        Gate::policy(EgresoCgu::class, EgresoCguPolicy::class);
        Gate::policy(ProcesoAdquisicion::class, ProcesoAdquisicionPolicy::class);

        Gate::before(fn (User $user, string $ability) => $user->hasRole('superadmin') ? true : null);

        Gate::after(function (User $user, string $ability, ?bool $result) {
            if ($result === false) {
                app(AuditLogger::class)->logSecurityEvent(
                    'acceso_denegado',
                    "Usuario {$user->id} sin permiso para '{$ability}'.",
                    ['ability' => $ability],
                    $user,
                );
            }
        });
    }
}
