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
            
            // Admins can only edit users in their own division
            if ($targetUser->division_id !== $user->division_id) {
                throw ValidationException::withMessages(['error' => 'You do not have permission to edit users outside your division.']);
            }
        }
        
        // Superadmins cannot edit Developers
        if ($user->hasRole('Superadmin') && !$user->hasRole('Developer')) {
            if ($targetUser->hasRole('Developer')) {
                throw ValidationException::withMessages(['error' => 'You do not have permission to edit a Developer.']);
            }
        }

        $data = $dto->toArray();

        // Admins cannot change division, cannot assign Admin/Superadmin/Developer roles, and cannot assign General Area
        if ($user->hasRole('Admin') && !$user->hasRole(['Developer', 'Superadmin'])) {
            $data['division_id'] = $user->division_id;
            
            $assignedArea = \App\Models\Area::find($dto->area_id);
            if ($assignedArea && strtolower(trim($assignedArea->area_name)) === 'general area') {
                throw ValidationException::withMessages(['area_id' => 'Admin accounts are not permitted to assign users to the General Area.']);
            }

            if ($dto->role && in_array($dto->role, ['Admin', 'Superadmin', 'Developer'])) {
                throw ValidationException::withMessages(['error' => 'You do not have permission to assign this role.']);
            }
        }
        
        // Superadmin cannot assign Superadmin or Developer roles
        if ($user->hasRole('Superadmin') && !$user->hasRole('Developer')) {
            if ($dto->role && in_array($dto->role, ['Superadmin', 'Developer'])) {
                throw ValidationException::withMessages(['error' => 'You do not have permission to assign this role.']);
            }
        }

        $targetUser->update($data);

        if ($dto->role) {
            $targetUser->syncRoles([$dto->role]);
        }

        return $targetUser;
    }
}
