<?php

namespace App\Http\Requests\ServiciosContratados;

use App\Models\ServicioContratado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServicioContratadoRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'contrato_id' => ['required', 'integer', 'exists:contratos,id'],
            ...$this->reglasComunes(),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function reglasComunes(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'categoria' => ['required', Rule::in(ServicioContratado::CATEGORIAS)],
            'monto_mensual_esperado' => ['required', 'numeric', 'min:0'],
            'moneda' => ['required', 'string', 'size:3'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'ccosto_id' => ['nullable', 'integer', 'exists:ccostos,id'],
            'administrador_funcionario_id' => ['nullable', 'integer', 'exists:funcionarios,id'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
