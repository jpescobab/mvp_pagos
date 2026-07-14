<?php

namespace App\Http\Requests\Maestros;

use App\Models\TipoDocumento;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreTipoDocumentoRequest extends FormRequest
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
            'codigo' => [
                'required',
                'string',
                'max:30',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (TipoDocumento::whereRaw('LOWER(codigo) = ?', [Str::lower((string) $value)])->exists()) {
                        $fail('Ya existe un tipo de documento con este código.');
                    }
                },
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'es_obligatorio' => ['boolean'],
            'activo' => ['boolean'],
        ];
    }
}
