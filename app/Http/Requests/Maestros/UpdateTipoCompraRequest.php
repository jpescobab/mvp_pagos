<?php

namespace App\Http\Requests\Maestros;

use App\Models\TipoCompra;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateTipoCompraRequest extends FormRequest
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
        /** @var TipoCompra $tipoCompra */
        $tipoCompra = $this->route('tipoCompra');

        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                function (string $attribute, mixed $value, Closure $fail) use ($tipoCompra): void {
                    $existe = TipoCompra::whereRaw('LOWER(codigo) = ?', [Str::lower((string) $value)])
                        ->where('id', '!=', $tipoCompra->id)
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe un tipo de compra con este código.');
                    }
                },
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'activo' => ['boolean'],
        ];
    }
}
