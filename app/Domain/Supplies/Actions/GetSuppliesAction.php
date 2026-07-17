<?php

namespace App\Domain\Supplies\Actions;

use App\Domain\Supplies\Models\Supply;
use Illuminate\Pagination\LengthAwarePaginator;

class GetSuppliesAction
{
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Supply::query();

        if (auth()->check()) {
            $user = auth()->user();
            if (!$user->hasRole(['Developer', 'Superadmin'])) {
                $query->where('department_id', $user->department_id);
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('article', 'like', "%{$search}%")
                  ->orWhere('stock_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category']) && $filters['category'] !== 'All') {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('id', 'desc')
                     ->paginate($filters['per_page'] ?? 10)
                     ->withQueryString();
    }
}
