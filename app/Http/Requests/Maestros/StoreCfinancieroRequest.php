<?php

namespace App\Http\Requests\Maestros;

use Illuminate\Foundation\Http\FormRequest;

class StoreCfinancieroRequest extends FormRequest
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
            'codigo' => ['required', 'string', 'max:255', 'unique:cfinancieros,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'jurisdiccion_id' => ['required', 'exists:jurisdicciones,id'],
            'activo' => ['boolean'],
        ];
    }
}
