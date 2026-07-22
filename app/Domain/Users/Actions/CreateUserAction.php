<?php

namespace App\Domain\Users\Actions;

use App\Models\User;
use App\Domain\Users\DTOs\UserDTO;

class CreateUserAction
{
    public function execute(UserDTO $dto): User
    {
        $data = $dto->toArray();
        $user = auth()->user();

        // If Admin is creating a user, force the division_id to the admin's division
        if ($user->hasRole('Admin') && !$user->hasRole(['Developer', 'Superadmin'])) {
            $data['division_id'] = $user->division_id;
            
            if (in_array($dto->role, ['Admin', 'Superadmin', 'Developer'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['error' => 'You do not have permission to assign this role.']);
            }
        }
        
        // Superadmin cannot assign Superadmin or Developer roles
        if ($user->hasRole('Superadmin') && !$user->hasRole('Developer')) {
            if (in_array($dto->role, ['Superadmin', 'Developer'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['error' => 'You do not have permission to assign this role.']);
            }
        }

        $newUser = User::create($data);

        if ($dto->role) {
            $newUser->assignRole($dto->role);
        }

        return $newUser;
    }
}
