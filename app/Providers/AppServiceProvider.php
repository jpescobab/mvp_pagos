<?php

namespace App\Providers;

use App\Listeners\Seguridad\RegistrarUltimoAcceso;
use App\Models\Asignacion;
use App\Models\AuditLog;
use App\Models\CasoPagoProveedor;
use App\Models\Catalogo;
use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\ClienteMedidor;
use App\Models\ConectorAutomatizacionNavegador;
use App\Models\DefinicionInformeRazonado;
use App\Models\EgresoCgu;
use App\Models\EjecucionInformeRazonado;
use App\Models\IndicadorEconomicoImportacion;
use App\Models\Item;
use App\Models\LicitacionMercadoPublico;
use App\Models\OrdenCompraMercadoPublico;
use App\Models\PeriodoReportabilidad;
use App\Models\Proceso;
use App\Models\ProcesoAdquisicion;
use App\Models\Proveedor;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use App\Models\User;
use App\Policies\AsignacionPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\CasoPagoProveedorPolicy;
use App\Policies\CatalogoPolicy;
use App\Policies\CcostoPolicy;
use App\Policies\CfinancieroPolicy;
use App\Policies\ClienteMedidorPolicy;
use App\Policies\ConectorAutomatizacionNavegadorPolicy;
use App\Policies\DefinicionInformeRazonadoPolicy;
use App\Policies\EgresoCguPolicy;
use App\Policies\EjecucionInformeRazonadoPolicy;
use App\Policies\IndicadorEconomicoImportacionPolicy;
use App\Policies\ItemPolicy;
use App\Policies\LicitacionMercadoPublicoPolicy;
use App\Policies\OrdenCompraMercadoPublicoPolicy;
use App\Policies\PeriodoReportabilidadPolicy;
use App\Policies\ProcesoAdquisicionPolicy;
use App\Policies\ProcesoPolicy;
use App\Policies\ProveedorPolicy;
use App\Policies\RolePolicy;
use App\Policies\TipoDocumentoPolicy;
use App\Policies\TipoProcesoPagoPolicy;
use App\Policies\UserPolicy;
use App\Services\AuditLogger;
use App\Services\Indicadores\IndicadorEconomicoSelector;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
        $this->app->singleton(IndicadorEconomicoSelector::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureEventListeners();
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
        Gate::policy(Proceso::class, ProcesoPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(ConectorAutomatizacionNavegador::class, ConectorAutomatizacionNavegadorPolicy::class);
        Gate::policy(Cfinanciero::class, CfinancieroPolicy::class);
        Gate::policy(Ccosto::class, CcostoPolicy::class);
        Gate::policy(Proveedor::class, ProveedorPolicy::class);
        Gate::policy(ClienteMedidor::class, ClienteMedidorPolicy::class);
        Gate::policy(Item::class, ItemPolicy::class);
        Gate::policy(Asignacion::class, AsignacionPolicy::class);
        Gate::policy(Catalogo::class, CatalogoPolicy::class);
        Gate::policy(PeriodoReportabilidad::class, PeriodoReportabilidadPolicy::class);
        Gate::policy(IndicadorEconomicoImportacion::class, IndicadorEconomicoImportacionPolicy::class);
        Gate::policy(DefinicionInformeRazonado::class, DefinicionInformeRazonadoPolicy::class);
        Gate::policy(EjecucionInformeRazonado::class, EjecucionInformeRazonadoPolicy::class);
        Gate::policy(OrdenCompraMercadoPublico::class, OrdenCompraMercadoPublicoPolicy::class);
        Gate::policy(LicitacionMercadoPublico::class, LicitacionMercadoPublicoPolicy::class);
        Gate::policy(TipoProcesoPago::class, TipoProcesoPagoPolicy::class);
        Gate::policy(TipoDocumento::class, TipoDocumentoPolicy::class);

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

    /**
     * Register application event listeners.
     */
    protected function configureEventListeners(): void
    {
        Event::listen(Login::class, RegistrarUltimoAcceso::class);
    }
}
