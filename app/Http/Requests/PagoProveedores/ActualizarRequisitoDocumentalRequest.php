<?php

namespace App\Http\Requests\PagoProveedores;

use App\Enums\TipoRequisitoDocumental;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarRequisitoDocumentalRequest extends FormRequest
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
            'tipo_proceso_pago_id' => ['nullable', 'exists:tipos_proceso_pago,id'],
            'tipo_requisito' => ['nullable', Rule::enum(TipoRequisitoDocumental::class)],
        ];
    }
}
