<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Models\EgresoCgu;
use App\Models\User;
use App\Services\PagoProveedores\RevisionEgresoPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RevisionPagosController extends Controller
{
    /**
     * Estados del workflow del caso que corresponden a una instancia de revisión.
     *
     * @var list<string>
     */
    private const ESTADOS_EN_REVISION = ['en_revision_finanzas', 'en_revision_zonal'];

    public function __construct(private readonly RevisionEgresoPresenter $presenter) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('pago-proveedores/revision/index', [
            'egresos' => $this->egresosEnRevision($user),
            'permisos' => [
                'revisar_finanzas' => $user->can('pago_proveedores.revisar_finanzas'),
                'revisar_zonal' => $user->can('pago_proveedores.revisar_zonal'),
            ],
        ]);
    }

    /**
     * Deep-link a un egreso concreto: se autoriza y se abre el workbench con
     * ese egreso preseleccionado.
     */
    public function show(EgresoCgu $egresoCgu, Request $request): Response|RedirectResponse
    {
        Gate::authorize('revisar', $egresoCgu);

        $user = $request->user();

        return Inertia::render('pago-proveedores/revision/index', [
            'egresos' => $this->egresosEnRevision($user),
            'egresoInicial' => $egresoCgu->id,
            'permisos' => [
                'revisar_finanzas' => $user->can('pago_proveedores.revisar_finanzas'),
                'revisar_zonal' => $user->can('pago_proveedores.revisar_zonal'),
            ],
        ]);
    }

    /**
     * Todos los egresos en revisión visibles para el usuario, con su detalle
     * completo (pagos + documentos) para alimentar el workbench de una sola
     * pantalla, tal como el prototipo.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function egresosEnRevision(User $user): Collection
    {
        return EgresoCgu::query()
            ->whereHas(
                'items.caso.proceso.estadoActual',
                fn ($query) => $query->whereIn('codigo', self::ESTADOS_EN_REVISION),
            )
            ->with('cfinanciero')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (EgresoCgu $egreso) => Gate::forUser($user)->allows('revisar', $egreso))
            ->map(fn (EgresoCgu $egreso) => $this->presenter->detalle($egreso, $user))
            ->values();
    }
}
