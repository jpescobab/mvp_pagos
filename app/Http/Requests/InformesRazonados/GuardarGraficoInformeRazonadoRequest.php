<?php

namespace App\Http\Requests\InformesRazonados;

use App\Models\EjecucionInformeRazonado;
use App\Models\GraficoInformeRazonado;
use Illuminate\Contracts\Validation\Validator;
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

    /**
     * Tipos que admiten una sola serie de datos.
     *
     * @var array<int, string>
     */
    public const TIPOS_SERIE_UNICA = ['torta'];

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
            'datos.categorias' => ['required', 'array', 'min:1'],
            'datos.categorias.*' => ['string', 'max:255'],
            'datos.series' => ['required', 'array', 'min:1'],
            'datos.series.*' => ['array'],
            'datos.series.*.nombre' => ['required', 'string', 'max:255'],
            'datos.series.*.valores' => ['required', 'array', 'min:1'],
            'datos.series.*.valores.*' => ['numeric'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'seccion_informe_razonado_id' => [
                'nullable',
                'integer',
                Rule::exists('secciones_informe_razonado', 'id')
                    ->where('ejecucion_informe_razonado_id', $this->ejecucionId()),
            ],
        ];
    }

    /**
     * Valida la coherencia interna de `datos`: cada serie debe tener tantos
     * valores como categorías, y los tipos de serie única (torta) admiten una
     * sola serie. Se corre después de las reglas de forma para no encadenar
     * errores sobre datos que ni siquiera son arrays.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $datos = $this->input('datos');

            if (! is_array($datos)) {
                return;
            }

            $categorias = $datos['categorias'] ?? null;
            $series = $datos['series'] ?? null;

            if (! is_array($categorias) || ! is_array($series)) {
                return;
            }

            $cantidadCategorias = count($categorias);

            foreach (array_values($series) as $indice => $serie) {
                $valores = is_array($serie) ? ($serie['valores'] ?? null) : null;

                if (is_array($valores) && count($valores) !== $cantidadCategorias) {
                    $validator->errors()->add(
                        "datos.series.{$indice}.valores",
                        'Cada serie debe tener la misma cantidad de valores que categorías.'
                    );
                }
            }

            if (in_array($this->input('tipo'), self::TIPOS_SERIE_UNICA, true) && count($series) > 1) {
                $validator->errors()->add(
                    'datos.series',
                    'Un gráfico de este tipo admite una sola serie de datos.'
                );
            }
        });
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
