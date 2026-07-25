<?php

namespace App\Http\Requests\InformesRazonados;

use Illuminate\Foundation\Http\FormRequest;

class GuardarExcepcionInformeRazonadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('informes.elaborar');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // Al editar (`update`) solo se ajustan descripción y severidad: el
        // código no se reescribe, así que no se exige en ese caso.
        $editando = $this->route('excepcion') !== null;

        return [
            'codigo' => $editando
                ? ['sometimes', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string'],
            'severidad' => ['required', 'in:info,advertencia,critico'],
        ];
    }
}
