<?php

namespace App\Services\Seguridad;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class GestionUsuariosService
{
    /** @var list<string> */
    private const ROLES_ADMINISTRADOR = ['admin', 'superadmin'];

    public function __construct(private readonly AuditLogger $auditLogger) {}

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
}
