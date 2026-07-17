<?php

namespace App\Domain\Users\Actions;

use App\Models\User;
use App\Domain\Users\DTOs\UserDTO;
use Illuminate\Validation\ValidationException;

class UpdateUserAction
{
    public function execute(User $targetUser, UserDTO $dto): User
    {
        $user = auth()->user();

        // Admins cannot edit other Admins, Superadmins, or Developers
        if ($user->hasRole('Admin') && !$user->hasRole(['Developer', 'Superadmin'])) {
            if ($targetUser->hasRole(['Admin', 'Superadmin', 'Developer'])) {
                throw ValidationException::withMessages(['error' => 'You do not have permission to edit this user.']);
            }
        }
        
        // Superadmins cannot edit Developers
        if ($user->hasRole('Superadmin') && !$user->hasRole('Developer')) {
            if ($targetUser->hasRole('Developer')) {
                throw ValidationException::withMessages(['error' => 'You do not have permission to edit a Developer.']);
            }
        }

        $data = $dto->toArray();

        // Admins cannot change department
        if ($user->hasRole('Admin') && !$user->hasRole(['Developer', 'Superadmin'])) {
            $data['department_id'] = $user->department_id;
        }

        $targetUser->update($data);

        if ($dto->role) {
            $targetUser->syncRoles([$dto->role]);
        }

        return $targetUser;
    }
}
