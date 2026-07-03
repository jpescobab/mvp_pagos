<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\StoreRoleRequest;
use App\Http\Requests\Seguridad\UpdateRoleRequest;
use App\Services\Seguridad\GestionRolesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RoleController extends Controller
{
    /** @var array<string, string> */
    private const GRUPOS_POR_PREFIJO = [
        'usuarios' => 'Administración',
        'roles' => 'Administración',
        'auditoria' => 'Administración',
        'core_institucional' => 'Administración',
        'documentos' => 'Administración',
        'tablas_maestras' => 'Maestros',
        'pago_proveedores' => 'Pago de Proveedores',
        'adquisiciones' => 'Adquisiciones',
        'informes' => 'Reportabilidad',
        'reportabilidad' => 'Reportabilidad',
        'integraciones' => 'Integraciones',
    ];

    private const GRUPO_OTROS = 'Otros';

    public function __construct(private readonly GestionRolesService $gestionRoles) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Role::class);

        $search = $request->string('search')->toString();

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        return Inertia::render('seguridad/roles/index', [
            'roles' => $roles->map(fn (Role $rol): array => [
                'id' => $rol->id,
                'name' => $rol->name,
                'users_count' => $rol->users_count,
                'permissions_count' => $rol->permissions_count,
                'is_core' => GestionRolesService::esRolCore($rol),
            ]),
            'filters' => [
                'search' => $search !== '' ? $search : null,
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Role::class);

        return Inertia::render('seguridad/roles/create', [
            'permissionGroups' => $this->gruposDePermisos(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $this->gestionRoles->crear([
            'name' => $datos['name'],
            'permissions' => $datos['permissions'] ?? [],
        ]);

        return to_route('roles.index');
    }

    public function edit(Role $role): Response
    {
        Gate::authorize('update', $role);

        return Inertia::render('seguridad/roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permission_ids' => $role->permissions()->pluck('id')->all(),
            ],
            'permissionGroups' => $this->gruposDePermisos(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $datos = $request->validated();

        $this->gestionRoles->editar($role, [
            'name' => $datos['name'],
            'permissions' => $datos['permissions'] ?? [],
        ]);

        return to_route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);

        try {
            $this->gestionRoles->eliminar($role);
        } catch (RuntimeException $e) {
            return Inertia::flash('error', $e->getMessage())->back();
        }

        return back();
    }

    /**
     * @return list<array{group: string, permissions: list<array{id: int, name: string}>}>
     */
    private function gruposDePermisos(): array
    {
        $permisosPorGrupo = [];

        foreach (Permission::orderBy('name')->get(['id', 'name']) as $permiso) {
            $grupo = self::GRUPOS_POR_PREFIJO[explode('.', $permiso->name)[0]] ?? self::GRUPO_OTROS;

            $permisosPorGrupo[$grupo][] = ['id' => (int) $permiso->id, 'name' => $permiso->name];
        }

        $resultado = [];

        foreach ($permisosPorGrupo as $grupo => $permisos) {
            $resultado[] = ['group' => $grupo, 'permissions' => $permisos];
        }

        return $resultado;
    }
}
