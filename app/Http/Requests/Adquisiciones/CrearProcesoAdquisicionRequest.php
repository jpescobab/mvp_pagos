<?php

namespace App\Http\Requests\Adquisiciones;

use App\Models\Funcionario;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CrearProcesoAdquisicionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fecha_inicio' => ['required', 'date'],
            'nombre' => ['required', 'string'],
            'id_requerimiento' => ['nullable', 'string'],
            'ccosto_id' => ['required', 'exists:ccostos,id'],
            'funcionario_requirente_id' => ['required', 'exists:funcionarios,id'],
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'caracteristicas' => ['required', 'string'],
            'motivo_contratacion' => ['required', 'string'],
            'en_plan_compras' => ['required', 'boolean'],
            'id_pac' => ['nullable', 'string'],
            'codigo_bip' => ['nullable', 'string'],
            'convenio_marco' => ['required', 'boolean'],
            'moneda_compra' => ['nullable', 'in:CLP,UF,USD'],
            'monto_estimado_solicitado' => ['required', 'numeric', 'min:0'],
            'fecha_paridad' => ['required_if:moneda_compra,UF,USD', 'nullable', 'date'],
        ];
    }

    /**
     * El funcionario requirente debe pertenecer a la unidad requirente
     * (ccosto_id) elegida — no basta con que exista, tiene que ser coherente
     * con la unidad seleccionada.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $funcionarioId = $this->input('funcionario_requirente_id');
            $ccostoId = $this->input('ccosto_id');

            if (! $funcionarioId || ! $ccostoId) {
                return;
            }

            $pertenece = Funcionario::where('id', $funcionarioId)
                ->where('ccosto_id', $ccostoId)
                ->exists();

            if (! $pertenece) {
                $validator->errors()->add(
                    'funcionario_requirente_id',
                    'El funcionario requirente debe pertenecer a la unidad requirente seleccionada.',
                );
            }
        });
    }
}
