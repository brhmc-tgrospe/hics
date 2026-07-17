<?php

namespace App\Domain\Users\Actions;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class GetUsersAction
{
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with(['division', 'roles']);

        $user = auth()->user();

        if ($user->hasRole('Admin') && !$user->hasRole(['Developer', 'Superadmin'])) {
            // Admin can see all users EXCEPT Developers and Superadmins
            $query->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['Developer', 'Superadmin']);
            });
        } elseif ($user->hasRole('Superadmin') && !$user->hasRole('Developer')) {
            // Superadmin can see everyone EXCEPT Developer
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Developer');
            });
        }

        $divisionOnly = $filters['division_only'] ?? ($user->hasRole('Admin') && !$user->hasRole(['Developer', 'Superadmin']));
        
        if ($divisionOnly === true || $divisionOnly === 'true') {
            $query->where('division_id', $user->division_id);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')
                     ->paginate($filters['per_page'] ?? 10)
                     ->withQueryString();
    }
}
