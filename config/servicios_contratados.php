<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ventana de alerta de vencimiento (días)
    |--------------------------------------------------------------------------
    |
    | Un servicio contratado no terminado cuya fecha_fin cae dentro de esta
    | ventana se muestra como "por vencer".
    |
    */

    'dias_alerta_vencimiento' => (int) env('SERVICIOS_CONTRATADOS_DIAS_ALERTA', 60),

];
