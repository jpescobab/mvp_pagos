<?php

namespace App\Http\Requests\InformesRazonados;

use Illuminate\Foundation\Http\FormRequest;

class RevisarNarrativaInformeRazonadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('informes.aprobar');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
