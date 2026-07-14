<?php

namespace App\Http\Requests\Maestros;

use App\Models\TipoDocumento;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateTipoDocumentoRequest extends FormRequest
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
        /** @var TipoDocumento $tipoDocumento */
        $tipoDocumento = $this->route('tipoDocumento');

        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                function (string $attribute, mixed $value, Closure $fail) use ($tipoDocumento): void {
                    $existe = TipoDocumento::whereRaw('LOWER(codigo) = ?', [Str::lower((string) $value)])
                        ->where('id', '!=', $tipoDocumento->id)
                        ->exists();

                    if ($existe) {
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
