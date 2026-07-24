<?php

namespace App\Http\Requests\InformesRazonados;

use App\Models\DefinicionInformeRazonado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarDefinicionInformeRazonadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('informes.administrar');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var DefinicionInformeRazonado $definicion */
        $definicion = $this->route('definicion');

        return [
            'codigo' => ['required', 'string', 'max:255', Rule::unique('definiciones_informe_razonado', 'codigo')->ignore($definicion->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];
    }
}
