<?php

namespace App\Http\Resources\Seguridad;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $funcionario = $this->funcionario;
        $cfinanciero = $funcionario?->cfinanciero;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rut' => $funcionario?->rut,
            'cargo' => $funcionario?->cargo,
            'unidad' => $funcionario?->unidad,
            'active' => $this->active,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'roles' => $this->roles->pluck('name')->values(),
            'jurisdiccion' => $cfinanciero?->jurisdiccion === null ? null : [
                'id' => $cfinanciero->jurisdiccion->id,
                'nombre' => $cfinanciero->jurisdiccion->nombre,
            ],
            'centro_financiero' => $cfinanciero === null ? null : [
                'id' => $cfinanciero->id,
                'nombre' => $cfinanciero->nombre,
            ],
            'centro_costo' => $funcionario?->ccosto === null ? null : [
                'id' => $funcionario->ccosto->id,
                'nombre' => $funcionario->ccosto->nombre,
            ],
        ];
    }
}
