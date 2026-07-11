<?php

namespace App\Http\Requests\PagoProveedores;

use App\Models\CasoPagoProveedor;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CrearEgresoCguRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('pago_proveedores.registrar_egreso');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'numero_egreso' => ['required', 'string', 'unique:egresos_cgu,numero_egreso'],
            'fecha' => ['required', 'date'],
            'observaciones' => ['nullable', 'string'],
            'casos' => ['required', 'array', 'min:1'],
            'casos.*.caso_pago_proveedor_id' => ['required', 'exists:casos_pago_proveedor,id'],
            'casos.*.monto' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Red de seguridad contra condición de carrera: dos usuarios podrían
     * abrir el formulario con la misma lista de casos pendientes y enviar el
     * mismo caso a dos egresos distintos. `exists:casos_pago_proveedor,id`
     * por sí solo no detecta que el caso ya quedó cubierto entre que se
     * cargó el formulario y se envió.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<int, array{caso_pago_proveedor_id?: int}> $casos */
            $casos = $this->input('casos', []);

            $casoIds = collect($casos)
                ->pluck('caso_pago_proveedor_id')
                ->filter()
                ->all();

            if ($casoIds === []) {
                return;
            }

            $yaAsignados = CasoPagoProveedor::whereIn('id', $casoIds)
                ->whereHas('egresoCguItems')
                ->pluck('sgf_id');

            if ($yaAsignados->isNotEmpty()) {
                $validator->errors()->add(
                    'casos',
                    'Los siguientes casos ya fueron asignados a otro egreso: '.$yaAsignados->implode(', ').'. Actualiza la página e inténtalo de nuevo.',
                );
            }
        });
    }
}
