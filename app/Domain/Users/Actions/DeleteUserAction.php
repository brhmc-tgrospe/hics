<?php

namespace App\Domain\Users\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteUserAction
{
    public function execute(User $targetUser): void
    {
        $user = auth()->user();

        // Admins cannot delete other Admins, Superadmins, or Developers
        if ($user->hasRole('Admin') && !$user->hasRole(['Developer', 'Superadmin'])) {
            if ($targetUser->hasRole(['Admin', 'Superadmin', 'Developer'])) {
                throw ValidationException::withMessages(['error' => 'You do not have permission to delete this user.']);
            }
        }
        
        // Superadmins cannot delete Developers
        if ($user->hasRole('Superadmin') && !$user->hasRole('Developer')) {
            if ($targetUser->hasRole('Developer')) {
                throw ValidationException::withMessages(['error' => 'You do not have permission to delete a Developer.']);
            }
        }

        $targetUser->delete();
    }
}
