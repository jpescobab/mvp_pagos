<?php

namespace App\Policies;

use App\Models\ProcesoAdquisicion;
use App\Models\User;

class ProcesoAdquisicionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProcesoAdquisicion $proceso): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
