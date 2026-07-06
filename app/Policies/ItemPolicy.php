<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function view(User $user, Item $item): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function update(User $user, Item $item): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
