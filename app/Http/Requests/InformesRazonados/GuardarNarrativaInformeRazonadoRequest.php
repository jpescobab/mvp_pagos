<?php

namespace App\Http\Requests\InformesRazonados;

use App\Models\EjecucionInformeRazonado;
use App\Models\NarrativaInformeRazonado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarNarrativaInformeRazonadoRequest extends FormRequest
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
        return [
            'contenido' => ['required', 'string', 'min:1'],
            'generado_por_ia' => ['nullable', 'boolean'],
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

        $narrativa = $this->route('narrativa');
        if ($narrativa instanceof NarrativaInformeRazonado) {
            return $narrativa->ejecucion_informe_razonado_id;
        }

        return null;
    }
}
