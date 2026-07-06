<?php

namespace App\Http\Requests\Maestros;

use App\Models\Cfinanciero;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCfinancieroRequest extends FormRequest
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
        /** @var Cfinanciero $cfinanciero */
        $cfinanciero = $this->route('cfinanciero');

        return [
            'codigo' => ['required', 'string', 'max:255', Rule::unique('cfinancieros', 'codigo')->ignore($cfinanciero->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'jurisdiccion_id' => ['required', 'exists:jurisdicciones,id'],
            'activo' => ['boolean'],
        ];
    }
}
