<?php

namespace App\Domain\Equipment\Actions;

use App\Domain\Equipment\Models\Equipment;
use Illuminate\Pagination\LengthAwarePaginator;

class GetEquipmentAction
{
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Equipment::query();

        if (!empty($filters['my_division_only']) && filter_var($filters['my_division_only'], FILTER_VALIDATE_BOOLEAN)) {
            if (auth()->check()) {
                $query->where('division_id', auth()->user()->division_id);
            }
        }

        if (!empty($filters['my_area_only']) && filter_var($filters['my_area_only'], FILTER_VALIDATE_BOOLEAN)) {
            if (auth()->check()) {
                $query->where('division_id', auth()->user()->division_id)
                      ->where('area_id', auth()->user()->area_id);
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('article', 'like', "%{$search}%")
                  ->orWhere('property_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
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
