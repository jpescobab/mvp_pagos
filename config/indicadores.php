<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Regla de fallback para indicadores diarios
    |--------------------------------------------------------------------------
    |
    | Cuando un cálculo requiere un indicador diario (USD) para una fecha sin
    | valor exacto registrado (día inhábil o sin publicación), se aplica esta
    | regla. Por ahora solo existe la estrategia 'ultimo_valor_disponible'
    | (usar el valor más reciente anterior a la fecha solicitada).
    |
    */

    'usd_fallback' => env('INDICADORES_USD_FALLBACK', 'ultimo_valor_disponible'),

];
