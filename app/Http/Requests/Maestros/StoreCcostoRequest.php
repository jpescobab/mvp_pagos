<?php

namespace App\Http\Requests\Maestros;

use Illuminate\Foundation\Http\FormRequest;

class StoreCcostoRequest extends FormRequest
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
            'codigo' => ['required', 'string', 'max:255', 'unique:ccostos,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'cfinanciero_id' => ['required', 'exists:cfinancieros,id'],
            'cod_edificio' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }
}
