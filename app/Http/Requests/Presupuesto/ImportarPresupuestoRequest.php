<?php

namespace App\Http\Requests\Presupuesto;

use App\Models\Presupuesto\ImportacionPresupuesto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ImportarPresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', ImportacionPresupuesto::class);

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:xlsx,xls'],
            'anio' => ['required', 'integer', 'min:2020'],
        ];
    }
}
