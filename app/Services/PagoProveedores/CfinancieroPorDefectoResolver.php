<?php

namespace App\Services\PagoProveedores;

use App\Models\Cfinanciero;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Resuelve el cfinanciero_id a usar cuando un caso_pago_proveedor no tiene
 * proceso_adquisicion vinculado, a partir del código configurado en
 * config('pago-proveedores.cfinanciero_default_codigo'). El resultado se
 * cachea con TTL corto porque los cfinancieros son datos maestros que
 * cambian con muy poca frecuencia.
 */
class CfinancieroPorDefectoResolver
{
    private const CACHE_TTL_MINUTOS = 60;

    private const CACHE_KEY = 'pago_proveedores:cfinanciero_default_id';

    public function resolver(): ?int
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTOS), function (): ?int {
            $codigo = config('pago-proveedores.cfinanciero_default_codigo');

            if ($codigo === null || $codigo === '') {
                return null;
            }

            $cfinancieroId = Cfinanciero::where('codigo', $codigo)->where('activo', true)->value('id');

            if ($cfinancieroId === null) {
                Log::warning('pago_proveedores.cfinanciero_default_no_resuelto', [
                    'codigo_configurado' => $codigo,
                ]);

                return null;
            }

            return $cfinancieroId;
        });
    }
}
