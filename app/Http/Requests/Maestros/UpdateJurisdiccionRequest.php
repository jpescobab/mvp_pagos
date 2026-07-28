<?php

namespace App\Http\Requests\Maestros;

use App\Models\Jurisdiccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJurisdiccionRequest extends FormRequest
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
        /** @var Jurisdiccion $jurisdiccion */
        $jurisdiccion = $this->route('jurisdiccion');

        return [
            'institucion_id' => ['required', 'exists:instituciones,id'],
            'codigo' => ['required', 'string', 'max:255', Rule::unique('jurisdicciones', 'codigo')->ignore($jurisdiccion->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }
}
