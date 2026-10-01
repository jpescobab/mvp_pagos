<?php

namespace App\Events;

use App\Models\Proceso;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Se emite después de confirmar la transacción de una transición de
 * workflow. Permite que los dominios funcionales reaccionen sin que el core
 * de workflow los conozca.
 */
class TransicionWorkflowEjecutada
{
    use Dispatchable;

    public function __construct(
        public readonly Proceso $proceso,
        public readonly string $transicionCodigo,
        public readonly ?User $user,
    ) {}
}
