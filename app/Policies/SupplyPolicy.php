<?php

namespace App\Policies;

use App\Models\User;
use App\Domain\Supplies\Models\Supply;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Supply $supply)
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

    public function update(User $user, Supply $supply)
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $user->division_id === $supply->division_id;
        }

        if ($user->hasRole('Encoder')) {
            return $user->division_id === $supply->division_id && $user->area_id === $supply->area_id;
        }

        return false;
    }

    public function delete(User $user, Supply $supply)
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $user->division_id === $supply->division_id;
        }

        if ($user->hasRole('Encoder')) {
            return $user->division_id === $supply->division_id && $user->area_id === $supply->area_id;
        }

        return false;
    }
}
