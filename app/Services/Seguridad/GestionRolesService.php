<?php

namespace App\Services\Seguridad;

use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;

class GestionRolesService
{
    /** @var list<string> */
    public const ROLES_CORE = ['superadmin', 'admin'];

    public function __construct(private readonly AuditLogger $auditLogger) {}

    public static function esRolCore(Role $rol): bool
    {
        return in_array($rol->name, self::ROLES_CORE, true);
    }

    /**
     * @param  array{name: string, permissions: array<int, int>}  $datos
     */
    public function crear(array $datos): Role
    {
        return DB::transaction(function () use ($datos): Role {
            $rol = Role::query()->create(['name' => $datos['name']]);
            $rol->syncPermissions($datos['permissions']);

            $this->auditLogger->log(
                'crear_rol',
                $rol,
                [],
                ['name' => $rol->name, 'permissions' => $datos['permissions']],
            );

            return $rol;
        });
    }

    /**
     * @param  array{name: string, permissions: array<int, int>}  $datos
     */
    public function editar(Role $rol, array $datos): void
    {
        DB::transaction(function () use ($rol, $datos): void {
            $before = [
                'name' => $rol->name,
                'permissions' => $rol->permissions()->pluck('id')->all(),
            ];

            $rol->forceFill(['name' => $datos['name']])->save();
            $rol->syncPermissions($datos['permissions']);

            $this->auditLogger->log(
                'editar_rol',
                $rol,
                $before,
                ['name' => $datos['name'], 'permissions' => $datos['permissions']],
            );
        });
    }

    public function eliminar(Role $rol): void
    {
        if (self::esRolCore($rol)) {
            throw new RuntimeException('No puede eliminar un rol core del sistema.');
        }

        if ($rol->users()->exists()) {
            throw new RuntimeException('No puede eliminar un rol que tiene usuarios asignados.');
        }

        $before = ['name' => $rol->name];

        $rol->delete();

        $this->auditLogger->log('eliminar_rol', $rol, $before, []);
    }
}
