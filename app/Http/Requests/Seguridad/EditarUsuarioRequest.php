<?php

namespace App\Http\Requests\Seguridad;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('usuarios.editar');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $usuario */
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'rut' => ['required', 'string', 'max:20', Rule::unique('funcionarios', 'rut')->ignore($usuario->funcionario?->id)],
            'cargo' => ['nullable', 'string', 'max:255'],
            'unidad' => ['nullable', 'string', 'max:255'],
            'cfinanciero_id' => ['nullable', 'exists:cfinancieros,id'],
            'ccosto_id' => ['nullable', 'exists:ccostos,id'],
        ];
    }
}
