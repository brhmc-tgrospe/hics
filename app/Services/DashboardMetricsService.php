<?php

namespace App\Services;

use App\Models\User;
use App\Models\Division;

class DashboardMetricsService
{
    /**
     * Get the equipment and supplies totals by division based on user roles.
     *
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    public function getDivisionTotals(User $user)
    {
        $isSuper = $user->hasRole(['Developer', 'Superadmin']);

        if ($isSuper) {
            return Division::select('id', 'div_name')
                ->withSum('equipment', 'total_value')
                ->withSum('supplies', 'total_amount')
                ->get()
                ->map(function ($div) {
                    return $this->formatDivisionData($div);
                });
        }

        return collect([]);
    }

    /**
     * Format the division data and calculate the total.
     *
     * @param Division $div
     * @return array
     */
    private function formatDivisionData(Division $div)
    {
        $equipmentValue = (float) ($div->equipment_sum_total_value ?? 0);
        $suppliesValue = (float) ($div->supplies_sum_total_amount ?? 0);

        return [
            'id' => $div->id,
            'name' => $div->div_name,
            'equipment_value' => $equipmentValue,
            'supplies_value' => $suppliesValue,
            'total' => $equipmentValue + $suppliesValue,
        ];
    }

    /**
     * Get discrepancy metrics and items based on user roles.
     *
     * @param User $user
     * @return array
     */
    public function getDiscrepancyMetrics(User $user)
    {
        $isSuper = $user->hasRole(['Developer', 'Superadmin']);

        $equipmentQuery = \App\Domain\Equipment\Models\Equipment::whereNotNull('shortage_overage_qty')
            ->where('shortage_overage_qty', '!=', 0);
        $suppliesQuery = \App\Domain\Supplies\Models\Supply::whereNotNull('shortage_overage_qty')
            ->where('shortage_overage_qty', '!=', 0);

        if (!$isSuper && $user->division_id) {
            $equipmentQuery->where('division_id', $user->division_id);
            $suppliesQuery->where('division_id', $user->division_id);
        } elseif (!$isSuper) {
            // User has no division and is not superadmin, return empty
            $equipmentQuery->where('id', 0);
            $suppliesQuery->where('id', 0);
        }

        $equipmentItems = $equipmentQuery->get(['id', 'article', 'description', 'property_number', 'shortage_overage_qty', 'shortage_overage_value', 'division_id']);
        $supplyItems = $suppliesQuery->get(['id', 'article', 'description', 'stock_number', 'shortage_overage_qty', 'shortage_overage_value', 'division_id']);

        $totalItemsCount = $equipmentItems->count() + $supplyItems->count();
        $totalValue = $equipmentItems->sum('shortage_overage_value') + $supplyItems->sum('shortage_overage_value');

        $formattedItems = collect();
        
        foreach ($equipmentItems as $item) {
            $formattedItems->push([
                'type' => 'Equipment',
                'id' => $item->id,
                'name' => $item->article . ($item->description ? ' - ' . $item->description : ''),
                'code' => $item->property_number,
                'qty' => $item->shortage_overage_qty,
                'value' => (float)$item->shortage_overage_value
            ]);
        }

        foreach ($supplyItems as $item) {
            $formattedItems->push([
                'type' => 'Supply',
                'id' => $item->id,
                'name' => $item->article . ($item->description ? ' - ' . $item->description : ''),
                'code' => $item->stock_number,
                'qty' => $item->shortage_overage_qty,
                'value' => (float)$item->shortage_overage_value
            ]);
        }

        return [
            'count' => $totalItemsCount,
            'value' => (float)$totalValue,
            'items' => $formattedItems->sortByDesc(function ($item) {
                return abs($item['value']);
            })->values()->all()
        ];
    }
}
