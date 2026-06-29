<?php

namespace App\Http\Requests\Integraciones;

use Illuminate\Foundation\Http\FormRequest;

class CrearConectorAutomatizacionNavegadorRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'sistema_externo_id' => ['required', 'integer', 'exists:sistemas_externos,id'],
            'codigo' => ['required', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ];
    }
}
