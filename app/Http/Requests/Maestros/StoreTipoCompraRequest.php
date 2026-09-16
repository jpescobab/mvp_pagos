<?php

namespace App\Http\Requests\Maestros;

use App\Models\TipoCompra;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreTipoCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('pago_proveedores.administrar_tipos_compra');
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
                    if (TipoCompra::whereRaw('LOWER(codigo) = ?', [Str::lower((string) $value)])->exists()) {
                        $fail('Ya existe un tipo de compra con este código.');
                    }
                },
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'activo' => ['boolean'],
        ];
    }
}
