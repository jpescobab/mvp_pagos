<?php

namespace App\Http\Requests\Integraciones;

use Illuminate\Foundation\Http\FormRequest;

class CrearPerfilAutenticacionNavegadorRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'almacen_secreto' => ['required', 'string', 'max:255'],
            'referencia_secreto' => ['required', 'string', 'max:255'],
        ];
    }
}
