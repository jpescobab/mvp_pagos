<?php

namespace App\Services\PagoProveedores;

use App\Models\CasoPagoProveedor;
use App\Models\EgresoCgu;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EgresoCguCreador
{
    public function __construct(private readonly RevisionEgresoService $revisionEgreso) {}

    /**
     * @param  array<string, mixed>  $datosValidados  Validado por CrearEgresoCguRequest: numero_egreso, fecha, observaciones?, casos (list de {caso_pago_proveedor_id, monto}).
     */
    public function crear(array $datosValidados, User $user): EgresoCgu
    {
        return DB::transaction(function () use ($datosValidados, $user) {
            $egreso = EgresoCgu::create([
                'numero_egreso' => $datosValidados['numero_egreso'],
                'fecha' => $datosValidados['fecha'],
                'observaciones' => $datosValidados['observaciones'] ?? null,
                'monto_total' => array_sum(array_column($datosValidados['casos'], 'monto')),
            ]);

            $casos = CasoPagoProveedor::with('proceso.estadoActual')
                ->whereIn('id', array_column($datosValidados['casos'], 'caso_pago_proveedor_id'))
                ->get()
                ->keyBy('id');

            foreach ($datosValidados['casos'] as $item) {
                $caso = $casos[$item['caso_pago_proveedor_id']];

                $egreso->items()->create([
                    'caso_pago_proveedor_id' => $item['caso_pago_proveedor_id'],
                    'monto' => $item['monto'],
                ]);

                $egreso->actualizarCfinancieroSiFalta($caso);

                // Recién asignado a un Egreso, el caso queda agrupado y
                // pasa a la instancia de revisión de Finanzas.
                $this->revisionEgreso->iniciarRevision($caso, $user);
            }

            return $egreso;
        });
    }
}
