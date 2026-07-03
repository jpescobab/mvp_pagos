<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\CrearUsuarioRequest;
use App\Http\Resources\Seguridad\UserResource;
use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\Jurisdiccion;
use App\Models\User;
use App\Services\Seguridad\GestionUsuariosService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserController extends Controller
{
    /** @var list<int> */
    private const TAMANOS_PAGINA = [15, 25, 50, 100];

    public function __construct(private readonly GestionUsuariosService $gestionUsuarios) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->string('search')->toString();
        $estado = $request->string('estado')->toString();
        $rolId = $request->integer('rol_id') ?: null;
        $jurisdiccionId = $request->integer('jurisdiccion_id') ?: null;
        $centroFinancieroId = $request->integer('centro_financiero_id') ?: null;
        $centroCostoId = $request->integer('centro_costo_id') ?: null;
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $perPage = in_array($request->integer('per_page'), self::TAMANOS_PAGINA, true)
            ? $request->integer('per_page')
            : 15;

        $usuarios = User::query()
            ->with(['roles', 'funcionario.cfinanciero.jurisdiccion', 'funcionario.ccosto'])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('funcionario', fn ($f) => $f->where('rut', 'like', "%{$search}%")),
            ))
            ->when($estado !== '', fn ($query) => $query->where('active', $estado === 'activo'))
            ->when($rolId !== null, fn ($query) => $query->whereHas('roles', fn ($r) => $r->where('id', $rolId)))
            ->when($jurisdiccionId !== null, fn ($query) => $query->whereHas(
                'funcionario.cfinanciero',
                fn ($c) => $c->where('jurisdiccion_id', $jurisdiccionId),
            ))
            ->when($centroFinancieroId !== null, fn ($query) => $query->whereHas(
                'funcionario',
                fn ($f) => $f->where('cfinanciero_id', $centroFinancieroId),
            ))
            ->when($centroCostoId !== null, fn ($query) => $query->whereHas(
                'funcionario',
                fn ($f) => $f->where('ccosto_id', $centroCostoId),
            ))
            ->when(
                in_array($sort, ['name', 'email', 'active', 'last_login_at', 'created_at'], true),
                fn ($query) => $query->orderBy($sort, $direction),
                fn ($query) => $query->orderByDesc('active')->orderBy('name'),
            )
            ->paginate($perPage)
            ->withQueryString();

        $permissions = [
            'can_create_user' => $request->user()->can('usuarios.crear'),
            'can_view_user' => $request->user()->can('usuarios.ver'),
            'can_edit_user' => $request->user()->can('usuarios.editar'),
            'can_activate_user' => $request->user()->can('usuarios.activar'),
            'can_deactivate_user' => $request->user()->can('usuarios.desactivar'),
            'can_reset_password' => $request->user()->can('usuarios.resetear_password'),
            'can_assign_roles' => $request->user()->can('usuarios.asignar_roles'),
        ];

        return Inertia::render('seguridad/usuarios/index', [
            'users' => UserResource::collection($usuarios),
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'estado' => $estado !== '' ? $estado : null,
                'rol_id' => $rolId,
                'jurisdiccion_id' => $jurisdiccionId,
                'centro_financiero_id' => $centroFinancieroId,
                'centro_costo_id' => $centroCostoId,
                'per_page' => $perPage,
                'sort' => $sort !== '' ? $sort : null,
                'direction' => $direction,
            ],
            'catalogs' => $this->catalogos(),
            'permissions' => $permissions,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('seguridad/usuarios/create', [
            'catalogs' => $this->catalogos(),
        ]);
    }

    public function store(CrearUsuarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $resultado = $this->gestionUsuarios->crear([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'rut' => $datos['rut'],
            'cargo' => $datos['cargo'] ?? null,
            'unidad' => $datos['unidad'] ?? null,
            'roles' => $datos['roles'],
            'cfinanciero_id' => $datos['cfinanciero_id'] ?? null,
            'ccosto_id' => $datos['ccosto_id'] ?? null,
        ]);

        Inertia::flash([
            'passwordTemporal' => $resultado['passwordTemporal'],
            'usuarioNombre' => $resultado['usuario']->name,
        ]);

        return to_route('usuarios.index');
    }

    public function activar(User $usuario): RedirectResponse
    {
        Gate::authorize('activar', $usuario);

        $this->gestionUsuarios->activar($usuario);

        return back();
    }

    public function desactivar(Request $request, User $usuario): RedirectResponse
    {
        Gate::authorize('desactivar', $usuario);

        try {
            $this->gestionUsuarios->desactivar($request->user(), $usuario);
        } catch (RuntimeException $e) {
            return Inertia::flash('error', $e->getMessage())->back();
        }

        return back();
    }

    public function resetPassword(User $usuario): RedirectResponse
    {
        Gate::authorize('resetearPassword', $usuario);

        $passwordTemporal = $this->gestionUsuarios->resetearPassword($usuario);

        return Inertia::flash([
            'passwordTemporal' => $passwordTemporal,
            'usuarioNombre' => $usuario->name,
        ])->back();
    }

    /**
     * @return array<string, Collection<int, mixed>>
     */
    private function catalogos(): array
    {
        return [
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'jurisdicciones' => Jurisdiccion::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'centros_financieros' => Cfinanciero::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'centros_costos' => Ccosto::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
        ];
    }
}
