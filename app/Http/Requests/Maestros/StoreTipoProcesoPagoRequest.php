<?php

namespace App\Http\Requests\Maestros;

use App\Models\TipoProcesoPago;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreTipoProcesoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('pago_proveedores.administrar_requisitos_documentales');
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
                    if (TipoProcesoPago::whereRaw('LOWER(codigo) = ?', [Str::lower((string) $value)])->exists()) {
                        $fail('Ya existe un tipo de proceso de pago con este código.');
                    }
                },
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'activo' => ['boolean'],
            'requiere_traspaso_cgu' => ['boolean'],
        ];
    }
}
