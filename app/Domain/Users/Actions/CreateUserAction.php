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

        // If Admin is creating a user, force the department_id to the admin's department
        if ($user->hasRole('Admin') && !$user->hasRole(['Developer', 'Superadmin'])) {
            $data['department_id'] = $user->department_id;
        }

        $newUser = User::create($data);

        if ($dto->role) {
            $newUser->assignRole($dto->role);
        }

        return $newUser;
    }
}
