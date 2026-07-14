<?php

namespace App\Enums;

enum TipoRequisitoDocumental: string
{
    case Obligatorio = 'obligatorio';
    case Opcional = 'opcional';
}
