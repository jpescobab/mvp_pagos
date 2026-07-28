<?php

namespace App\Http\Requests\InformesRazonados;

use App\Models\EjecucionInformeRazonado;
use App\Models\MetricaInformeRazonado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMetricaInformeRazonadoRequest extends FormRequest
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
        // Al editar (`update`) solo se ajustan los datos de la métrica: el código
        // no se reescribe, así que no se exige en ese caso.
        $editando = $this->route('metrica') !== null;

        return [
            'codigo' => $editando
                ? ['sometimes', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'etiqueta' => ['required', 'string', 'max:255'],
            'valor' => ['nullable', 'numeric'],
            'unidad' => ['nullable', 'string', 'max:255'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'seccion_informe_razonado_id' => [
                'nullable',
                'integer',
                Rule::exists('secciones_informe_razonado', 'id')
                    ->where('ejecucion_informe_razonado_id', $this->ejecucionId()),
            ],
        ];
    }

    private function ejecucionId(): ?int
    {
        $ejecucion = $this->route('ejecucion');
        if ($ejecucion instanceof EjecucionInformeRazonado) {
            return $ejecucion->id;
        }

        $metrica = $this->route('metrica');
        if ($metrica instanceof MetricaInformeRazonado) {
            return $metrica->ejecucion_informe_razonado_id;
        }

        return null;
    }
}
