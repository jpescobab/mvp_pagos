<?php

namespace App\Http\Requests\ServiciosContratados;

class UpdateServicioContratadoRequest extends StoreServicioContratadoRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->reglasComunes();
    }
}
