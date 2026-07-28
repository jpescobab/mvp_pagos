<?php

namespace App\Http\Requests\InformesRazonados;

use App\Models\EjecucionInformeRazonado;
use App\Models\GraficoInformeRazonado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarGraficoInformeRazonadoRequest extends FormRequest
{
    /**
     * Tipos de gráfico admitidos por el backend.
     *
     * @var array<int, string>
     */
    public const TIPOS = ['barra', 'linea', 'torta', 'area'];

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('informes.elaborar');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // Al editar (`update`) el código no se reescribe, así que no se exige.
        $editando = $this->route('grafico') !== null;

        return [
            'codigo' => $editando
                ? ['sometimes', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'titulo' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', Rule::in(self::TIPOS)],
            'datos' => ['required', 'array'],
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

        $grafico = $this->route('grafico');
        if ($grafico instanceof GraficoInformeRazonado) {
            return $grafico->ejecucion_informe_razonado_id;
        }

        return null;
    }
}
