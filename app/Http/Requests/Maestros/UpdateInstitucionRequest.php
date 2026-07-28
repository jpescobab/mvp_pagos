<?php

namespace App\Http\Requests\Maestros;

use App\Models\Institucion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstitucionRequest extends FormRequest
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
        /** @var Institucion $institucion */
        $institucion = $this->route('institucion');

        return [
            'codigo' => ['required', 'string', 'max:255', Rule::unique('instituciones', 'codigo')->ignore($institucion->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }
}
