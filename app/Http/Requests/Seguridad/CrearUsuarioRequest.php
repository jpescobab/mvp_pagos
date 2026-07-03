<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class CrearUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('usuarios.crear');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'rut' => ['required', 'string', 'max:20', 'unique:funcionarios,rut'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'unidad' => ['nullable', 'string', 'max:255'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'cfinanciero_id' => ['nullable', 'exists:cfinancieros,id'],
            'ccosto_id' => ['nullable', 'exists:ccostos,id'],
        ];
    }
}
