<?php

namespace App\Policies;

use App\Models\CasoPagoProveedor;
use App\Models\User;

class CasoPagoProveedorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CasoPagoProveedor $caso): bool
    {
        return true;
    }
}
