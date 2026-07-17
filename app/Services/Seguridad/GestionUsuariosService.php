<?php

namespace App\Services\Seguridad;

use App\Models\Funcionario;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class GestionUsuariosService
{
    /** @var list<string> */
    private const ROLES_ADMINISTRADOR = ['admin', 'superadmin'];

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PermisosCompartidosResolver $permisosCompartidos,
    ) {}

    /**
     * @param  array{name: string, email: string, rut: string, cargo: ?string, unidad: ?string, roles: array<int, int>, cfinanciero_id: ?int, ccosto_id: ?int}  $datos
     * @return array{usuario: User, passwordTemporal: string}
     */
    public function crear(array $datos): array
    {
        return DB::transaction(function () use ($datos): array {
            $passwordTemporal = Str::password();

            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => Hash::make($passwordTemporal),
            ]);

            $usuario->forceFill(['must_change_password' => true])->save();

            Funcionario::create([
                'user_id' => $usuario->id,
                'rut' => $datos['rut'],
                'nombre' => $datos['name'],
                'cargo' => $datos['cargo'] ?? null,
                'unidad' => $datos['unidad'] ?? null,
                'cfinanciero_id' => $datos['cfinanciero_id'] ?? null,
                'ccosto_id' => $datos['ccosto_id'] ?? null,
            ]);

            // Usuario recién creado en esta misma transacción: no puede
            // existir una entrada de caché de permisos previa que invalidar.
            $usuario->syncRoles($datos['roles']);

            $this->auditLogger->log(
                'crear_usuario',
                $usuario,
                [],
                ['name' => $usuario->name, 'email' => $usuario->email, 'roles' => $datos['roles']],
            );

            return ['usuario' => $usuario, 'passwordTemporal' => $passwordTemporal];
        });
    }

    /**
     * Actualiza los datos personales e institucionales del usuario.
     * No toca roles, contraseña ni estado activo.
     *
     * @param  array{name: string, email: string, rut: string, cargo: ?string, unidad: ?string, cfinanciero_id: ?int, ccosto_id: ?int}  $datos
     */
    public function editar(User $usuario, array $datos): void
    {
        DB::transaction(function () use ($usuario, $datos): void {
            $funcionario = $usuario->funcionario;

            $before = [
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rut' => $funcionario?->rut,
                'cargo' => $funcionario?->cargo,
                'unidad' => $funcionario?->unidad,
                'cfinanciero_id' => $funcionario?->cfinanciero_id,
                'ccosto_id' => $funcionario?->ccosto_id,
            ];

            $usuario->forceFill([
                'name' => $datos['name'],
                'email' => $datos['email'],
            ])->save();

            Funcionario::updateOrCreate(
                ['user_id' => $usuario->id],
                [
                    'rut' => $datos['rut'],
                    'nombre' => $datos['name'],
                    'cargo' => $datos['cargo'] ?? null,
                    'unidad' => $datos['unidad'] ?? null,
                    'cfinanciero_id' => $datos['cfinanciero_id'] ?? null,
                    'ccosto_id' => $datos['ccosto_id'] ?? null,
                ],
            );

            $this->auditLogger->log(
                'editar_usuario',
                $usuario,
                $before,
                [
                    'name' => $datos['name'],
                    'email' => $datos['email'],
                    'rut' => $datos['rut'],
                    'cargo' => $datos['cargo'] ?? null,
                    'unidad' => $datos['unidad'] ?? null,
                    'cfinanciero_id' => $datos['cfinanciero_id'] ?? null,
                    'ccosto_id' => $datos['ccosto_id'] ?? null,
                ],
            );
        });
    }

    public function activar(User $usuario): void
    {
        $before = ['active' => $usuario->active];

        $usuario->forceFill(['active' => true])->save();

        $this->auditLogger->log(
            'activar_usuario',
            $usuario,
            $before,
            ['active' => true],
        );
    }

    public function desactivar(User $actor, User $usuario): void
    {
        if ($actor->is($usuario)) {
            throw new RuntimeException('No puede desactivar su propia cuenta.');
        }

        if ($this->esUltimoAdministradorActivo($usuario)) {
            throw new RuntimeException('No puede desactivar al último Administrador del Sistema activo.');
        }

        $before = ['active' => $usuario->active];

        $usuario->forceFill(['active' => false])->save();

        $this->auditLogger->log(
            'desactivar_usuario',
            $usuario,
            $before,
            ['active' => false],
        );
    }

    /**
     * @param  array<int, int>  $roles
     */
    public function asignarRoles(User $usuario, array $roles): void
    {
        $rolesActuales = $usuario->roles()->pluck('roles.id')->all();

        if ($this->quitaRolDeAdministradorAlUltimoActivo($usuario, $roles)) {
            throw new RuntimeException('No puede quitarle el rol de Administrador del Sistema al último Administrador del Sistema activo.');
        }

        $usuario->syncRoles($roles);

        $this->permisosCompartidos->invalidarParaUsuario($usuario->id);

        $this->auditLogger->log(
            'reasignar_roles_usuario',
            $usuario,
            ['roles' => $rolesActuales],
            ['roles' => $roles],
        );
    }

    public function resetearPassword(User $usuario): string
    {
        $passwordTemporal = Str::password();

        $usuario->forceFill([
            'password' => Hash::make($passwordTemporal),
            'must_change_password' => true,
        ])->save();

        $this->auditLogger->log(
            'resetear_password_usuario',
            $usuario,
            ['must_change_password' => false],
            ['must_change_password' => true],
        );

        return $passwordTemporal;
    }

    private function esUltimoAdministradorActivo(User $usuario): bool
    {
        if (! $usuario->active || ! $usuario->hasAnyRole(self::ROLES_ADMINISTRADOR)) {
            return false;
        }

        $otrosAdministradoresActivos = User::query()
            ->where('active', true)
            ->whereKeyNot($usuario->getKey())
            ->role(self::ROLES_ADMINISTRADOR)
            ->exists();

        return ! $otrosAdministradoresActivos;
    }

    /**
     * @param  array<int, int>  $rolesNuevos
     */
    private function quitaRolDeAdministradorAlUltimoActivo(User $usuario, array $rolesNuevos): bool
    {
        if (! $this->esUltimoAdministradorActivo($usuario)) {
            return false;
        }

        $idsRolesAdministrador = Role::whereIn('name', self::ROLES_ADMINISTRADOR)->pluck('id')->all();

        return array_intersect($idsRolesAdministrador, $rolesNuevos) === [];
    }
}
