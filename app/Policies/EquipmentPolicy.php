<?php

namespace App\Policies;

use App\Models\User;
use App\Domain\Equipment\Models\Equipment;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Equipment $equipment)
    {
        return true;
    }

    public function create(User $user)
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole(['Admin', 'Encoder'])) {
            return true;
        }

        return false;
    }

    public function update(User $user, Equipment $equipment)
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $user->division_id === $equipment->division_id;
        }

        if ($user->hasRole('Encoder')) {
            return $user->division_id === $equipment->division_id && $user->area_id === $equipment->area_id;
        }

        return false;
    }

    public function delete(User $user, Equipment $equipment)
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $user->division_id === $equipment->division_id;
        }

        if ($user->hasRole('Encoder')) {
            return $user->division_id === $equipment->division_id && $user->area_id === $equipment->area_id;
        }

        return false;
    }
}
