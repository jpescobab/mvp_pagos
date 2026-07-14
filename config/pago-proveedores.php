<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Centro financiero por defecto
    |--------------------------------------------------------------------------
    |
    | Código institucional del cfinanciero a usar cuando un caso_pago_proveedor
    | no tiene un proceso_adquisicion vinculado (y por lo tanto no hay forma de
    | derivar su centro financiero real vía caso -> proceso_adquisicion ->
    | ccosto -> cfinanciero). El vínculo real siempre tiene prioridad sobre
    | este default. Ver CfinancieroPorDefectoResolver.
    |
    */

    'cfinanciero_default_codigo' => env('PAGO_PROVEEDORES_CFINANCIERO_DEFAULT_CODIGO', '1400'),

];
