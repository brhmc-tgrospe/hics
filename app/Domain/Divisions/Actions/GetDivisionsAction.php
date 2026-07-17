<?php

namespace App\Domain\Divisions\Actions;

use App\Models\Division;

class GetDivisionsAction
{
    public function execute(array $filters = [])
    {
        $query = Division::query();

        if (isset($filters['search']) && $filters['search']) {
            $query->where('div_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('div_code', 'like', '%' . $filters['search'] . '%');
        }

        $perPage = $filters['per_page'] ?? 10;
        return $query->paginate($perPage)->withQueryString();
    }
}
