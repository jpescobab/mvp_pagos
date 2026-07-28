<?php

namespace App\Http\Requests\InformesRazonados;

use App\Services\InformesRazonados\ExportadorInformeRazonadoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerarExportacionInformeRazonadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('informes.exportar');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'formato' => ['required', 'string', Rule::in(ExportadorInformeRazonadoService::FORMATOS_SOPORTADOS)],
        ];
    }
}
