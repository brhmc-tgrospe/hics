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
     * Get aggregate metrics for equipment and supplies based on user roles.
     *
     * @param User $user
     * @return array
     */
    public function getAggregateMetrics(User $user)
    {
        $isSuper = $user->hasRole(['Developer', 'Superadmin']);

        $equipmentQuery = \Illuminate\Support\Facades\DB::table('equipment')->whereNull('deleted_at');
        $suppliesQuery = \Illuminate\Support\Facades\DB::table('supplies')->whereNull('deleted_at');

        if (!$isSuper) {
            if ($user->division_id) {
                $equipmentQuery->where('division_id', $user->division_id);
                $suppliesQuery->where('division_id', $user->division_id);
            } else {
                return [
                    'equipmentCount' => 0,
                    'suppliesCount' => 0,
                    'equipmentValue' => 0,
                    'suppliesValue' => 0,
                    'equipmentByCategory' => [],
                    'suppliesByCategory' => []
                ];
            }
        }

        return [
            'equipmentCount' => $equipmentQuery->count(),
            'suppliesCount' => $suppliesQuery->count(),
            'equipmentValue' => (float) $equipmentQuery->sum('total_value'),
            'suppliesValue' => (float) $suppliesQuery->sum('total_amount'),
            'equipmentByCategory' => $equipmentQuery->select('category', \Illuminate\Support\Facades\DB::raw('count(*) as count'))->groupBy('category')->pluck('count', 'category')->toArray(),
            'suppliesByCategory' => $suppliesQuery->select('category', \Illuminate\Support\Facades\DB::raw('count(*) as count'))->groupBy('category')->pluck('count', 'category')->toArray(),
        ];
    }

    /**
     * Get discrepancy metrics and items based on user roles, paginated.
     *
     * @param User $user
     * @param int $perPage
     * @return array
     */
    public function getDiscrepancyMetrics(User $user, $perPageQty = 5, $perPageValue = 5)
    {
        $isSuper = $user->hasRole(['Developer', 'Superadmin']);

        $eqQuery = \Illuminate\Support\Facades\DB::table('equipment')
            ->whereNotNull('shortage_overage_qty')
            ->where('shortage_overage_qty', '!=', 0)
            ->whereNull('deleted_at')
            ->selectRaw("'Equipment' as type, id, article as name, description, property_number as code, shortage_overage_qty as qty, shortage_overage_value as value");

        $supQuery = \Illuminate\Support\Facades\DB::table('supplies')
            ->whereNotNull('shortage_overage_qty')
            ->where('shortage_overage_qty', '!=', 0)
            ->whereNull('deleted_at')
            ->selectRaw("'Supply' as type, id, article as name, description, stock_number as code, shortage_overage_qty as qty, shortage_overage_value as value");

        if (!$isSuper && $user->division_id) {
            $eqQuery->where('division_id', $user->division_id);
            $supQuery->where('division_id', $user->division_id);
        } elseif (!$isSuper) {
            $eqQuery->where('id', 0);
            $supQuery->where('id', 0);
        }

        $unionQuery = $eqQuery->unionAll($supQuery);

        $totalValueResult = \Illuminate\Support\Facades\DB::table(\Illuminate\Support\Facades\DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery)
            ->selectRaw('SUM(value) as total_value, COUNT(*) as total_count')
            ->first();

        $paginatedQtyItems = \Illuminate\Support\Facades\DB::table(\Illuminate\Support\Facades\DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery)
            ->orderByRaw('ABS(qty) DESC')
            ->paginate($perPageQty, ['*'], 'page_qty')
            ->withQueryString();

        $paginatedValueItems = \Illuminate\Support\Facades\DB::table(\Illuminate\Support\Facades\DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery)
            ->orderByRaw('ABS(value) DESC')
            ->paginate($perPageValue, ['*'], 'page_value')
            ->withQueryString();

        return [
            'count' => $totalValueResult->total_count ?? 0,
            'value' => (float)($totalValueResult->total_value ?? 0),
            'items_qty' => $paginatedQtyItems,
            'items_value' => $paginatedValueItems
        ];
    }
}
