<?php

namespace App\Domain\Users\Actions;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class GetUsersAction
{
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with(['department', 'roles']);

        $user = auth()->user();

        // Security scoping
        if ($user->hasRole('Admin')) {
            // Admin can see users in their department, EXCEPT developers. Wait, Superadmin should also be hidden from Admin
            $query->where('department_id', $user->department_id)
                  ->whereDoesntHave('roles', function ($q) {
                      $q->whereIn('name', ['Developer', 'Superadmin']);
                  });
        } elseif ($user->hasRole('Superadmin')) {
            // Superadmin can see everyone EXCEPT Developer
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Developer');
            });
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
