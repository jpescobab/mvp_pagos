<?php

namespace App\Listeners\Seguridad;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class RegistrarUltimoAcceso
{
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $user->forceFill(['last_login_at' => now()])->save();
    }
}
