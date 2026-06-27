<?php

namespace App\Services\Adquisiciones;

use App\Exceptions\ProcesoAdquisicionException;
use App\Models\DefinicionWorkflow;
use App\Models\ModalidadAdquisicion;
use App\Models\Proceso;
use App\Models\ProcesoAdquisicion;
use Illuminate\Support\Facades\DB;

class ProcesoAdquisicionService
{
    /**
     * @param  array<string, mixed>  $datos  Debe incluir codigo, modalidad_id, ccosto_id, objeto y opcionalmente proveedor_id/monto
     */
    public function crear(array $datos): ProcesoAdquisicion
    {
        $modalidad = ModalidadAdquisicion::where('id', $datos['modalidad_id'])
            ->where('activo', true)
            ->first();

        if ($modalidad === null) {
            throw ProcesoAdquisicionException::modalidadInvalida();
        }

        return DB::transaction(function () use ($datos) {
            $proceso = ProcesoAdquisicion::create($datos);

            $definicion = DefinicionWorkflow::where('codigo', 'adquisiciones')->firstOrFail();
            $estadoInicial = $definicion->estados()->where('es_inicial', true)->firstOrFail();

            Proceso::create([
                'definicion_workflow_id' => $definicion->id,
                'estado_actual_id' => $estadoInicial->id,
                'sujeto_type' => ProcesoAdquisicion::class,
                'sujeto_id' => $proceso->id,
                'modalidad_id' => $proceso->modalidad_id,
                'monto' => $proceso->monto,
            ]);

            return $proceso->refresh();
        });
    }
}
