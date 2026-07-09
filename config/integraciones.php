<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Umbral de detección de trabajos_integracion huérfanos
    |--------------------------------------------------------------------------
    |
    | Un trabajo_integracion en estado "en_progreso" cuyo iniciado_en supera
    | este umbral (en minutos) se trata como huérfano: el proceso que lo
    | ejecutaba probablemente murió sin poder reportar ni éxito ni error
    | (timeout del worker de la cola, terminal cerrada, equipo suspendido,
    | etc. — ver services/sgf-playwright/CALIBRACION.md y HARNESS_IA.md
    | sección 13 para el caso real que motivó esto).
    |
    | Cada clave es el "tipo" de trabajo_integracion (ej. "importar_pendientes",
    | "verificar_caso"). "default" aplica a cualquier tipo no listado
    | explícitamente. Deben quedar por encima de los timeouts ya conocidos
    | del Job/HTTP/cola correspondiente, para no marcar como huérfano un
    | trabajo que en realidad sigue corriendo legítimamente.
    |
    */

    'umbral_huerfano_minutos' => [
        'importar_pendientes' => env('INTEGRACIONES_UMBRAL_HUERFANO_IMPORTAR_PENDIENTES_MINUTOS', 90),
        'importar_grupo_pago_operaciones' => env('INTEGRACIONES_UMBRAL_HUERFANO_IMPORTAR_GRUPO_PAGO_OPERACIONES_MINUTOS', 90),
        'verificar_caso' => env('INTEGRACIONES_UMBRAL_HUERFANO_VERIFICAR_CASO_MINUTOS', 10),
        'default' => env('INTEGRACIONES_UMBRAL_HUERFANO_DEFAULT_MINUTOS', 120),
    ],

];
