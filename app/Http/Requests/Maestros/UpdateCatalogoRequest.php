<?php

namespace App\Http\Requests\Maestros;

use App\Models\Catalogo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCatalogoRequest extends FormRequest
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
        /** @var Catalogo $catalogo */
        $catalogo = $this->route('catalogo');

        return [
            'codigo' => ['required', 'string', 'max:255', Rule::unique('catalogos', 'codigo')->ignore($catalogo->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];
    }
}
