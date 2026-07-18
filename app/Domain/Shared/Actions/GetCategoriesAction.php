<?php

namespace App\Domain\Shared\Actions;

use App\Domain\Shared\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

class GetCategoriesAction
{
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Category::query();

        // Filter by type (equipment or supply)
        $type = $filters['tab'] ?? 'equipment';
        if ($type === 'supplies') {
            $type = 'supply';
        }
        $query->where('type', $type);

        // Global search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')
                     ->paginate($filters['per_page'] ?? 10)
                     ->withQueryString();
    }
}
