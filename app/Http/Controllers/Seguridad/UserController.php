<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\CrearUsuarioRequest;
use App\Http\Requests\Seguridad\EditarUsuarioRequest;
use App\Http\Resources\Seguridad\AuditLogResource;
use App\Http\Resources\Seguridad\SecurityAuditLogResource;
use App\Http\Resources\Seguridad\UserResource;
use App\Models\Ccosto;
use App\Models\Cfinanciero;
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
                'per_page' => $perPage,
                'sort' => $sort !== '' ? $sort : null,
                'direction' => $direction,
            ],
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

    public function show(Request $request, User $usuario): Response
    {
        Gate::authorize('view', $usuario);

        $usuario->load(['roles', 'funcionario.cfinanciero.jurisdiccion', 'funcionario.ccosto']);

        $actividad = $this->gestionUsuarios->actividadReciente($usuario);

        return Inertia::render('seguridad/usuarios/show', [
            'usuario' => new UserResource($usuario),
            'permisos_efectivos' => $this->gestionUsuarios->permisosEfectivos($usuario),
            'actividad' => [
                'negocio' => AuditLogResource::collection($actividad['negocio']),
                'seguridad' => SecurityAuditLogResource::collection($actividad['seguridad']),
            ],
            'permissions' => [
                'can_edit_user' => (bool) $request->user()?->can('usuarios.editar'),
                'can_activate_user' => (bool) $request->user()?->can('usuarios.activar'),
                'can_deactivate_user' => (bool) $request->user()?->can('usuarios.desactivar'),
                'can_reset_password' => (bool) $request->user()?->can('usuarios.resetear_password'),
            ],
        ]);
    }

    public function edit(Request $request, User $usuario): Response
    {
        Gate::authorize('update', $usuario);

        $funcionario = $usuario->funcionario;

        return Inertia::render('seguridad/usuarios/edit', [
            'usuario' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rut' => $funcionario?->rut,
                'cargo' => $funcionario?->cargo,
                'unidad' => $funcionario?->unidad,
                'cfinanciero_id' => $funcionario?->cfinanciero_id,
                'ccosto_id' => $funcionario?->ccosto_id,
                'role_ids' => $usuario->roles()->pluck('roles.id')->all(),
            ],
            'catalogs' => $this->catalogos(),
            'permissions' => [
                'can_assign_roles' => (bool) $request->user()?->can('usuarios.asignar_roles'),
            ],
        ]);
    }

    public function update(EditarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

        $this->gestionUsuarios->editar($usuario, [
            'name' => $datos['name'],
            'email' => $datos['email'],
            'rut' => $datos['rut'],
            'cargo' => $datos['cargo'] ?? null,
            'unidad' => $datos['unidad'] ?? null,
            'cfinanciero_id' => $datos['cfinanciero_id'] ?? null,
            'ccosto_id' => $datos['ccosto_id'] ?? null,
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

    public function actualizarRoles(Request $request, User $usuario): RedirectResponse
    {
        Gate::authorize('asignarRoles', $usuario);

        $datos = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        try {
            $this->gestionUsuarios->asignarRoles($usuario, $datos['roles'] ?? []);
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
            'centros_financieros' => Cfinanciero::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'centros_costos' => Ccosto::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
        ];
    }
}
