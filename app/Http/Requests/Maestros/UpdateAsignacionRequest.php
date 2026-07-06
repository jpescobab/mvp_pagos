<?php

namespace App\Http\Requests\Maestros;

use App\Models\Asignacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAsignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('core_institucional.administrar');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Asignacion $asignacion */
        $asignacion = $this->route('asignacion');

        return [
            'codigo' => ['required', 'string', 'max:255', Rule::unique('asignaciones', 'codigo')->ignore($asignacion->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];
    }
}
