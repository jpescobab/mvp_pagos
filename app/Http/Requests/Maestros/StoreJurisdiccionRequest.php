<?php

namespace App\Http\Requests\Maestros;

use Illuminate\Foundation\Http\FormRequest;

class StoreJurisdiccionRequest extends FormRequest
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
        return [
            'institucion_id' => ['required', 'exists:instituciones,id'],
            'codigo' => ['required', 'string', 'max:255', 'unique:jurisdicciones,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }
}
