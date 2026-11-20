<?php

namespace App\Domain\Supplies\Actions;

use App\Domain\Shared\Models\Category;
use App\Domain\Supplies\Models\Supply;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetExpiringSuppliesAction
{
    /**
     * Retrieve supplies expiring on or before 6 months from the current date in UTC+8,
     * grouped by category with category and grand totals for quantity and amount.
     */
    public function execute(?User $user = null, array $filters = [], bool $enforceRoleScope = false): array
    {
        $nowUtc8 = Carbon::now('+08:00')->startOfDay();
        $cutoffDateUtc8 = $nowUtc8->copy()->addMonths(6)->endOfDay();
        $todayStr = $nowUtc8->toDateString();
        $cutoffStr = $cutoffDateUtc8->toDateString();

        $query = Supply::with([
            'division' => fn($q) => $q->withTrashed()->select('id', 'div_name'),
            'area' => fn($q) => $q->withTrashed()->select('id', 'area_name', 'division_id'),
        ])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $cutoffStr);

        if ($enforceRoleScope && $user !== null && !$user->hasRole(['Developer', 'Superadmin', 'Secretary'])) {
            if ($user->division_id) {
                $query->where('division_id', $user->division_id);
            } else {
                return $this->emptyResult($todayStr, $cutoffStr);
            }
        }

        $this->applyFilters($query, $filters, $user);

        $supplies = $query
            ->orderBy('expiry_date', 'asc')
            ->orderBy('article', 'asc')
            ->get();

        if ($supplies->isEmpty()) {
            return $this->emptyResult($todayStr, $cutoffStr);
        }

        $categories = Category::where('type', 'supply')->get();

        return $this->groupSuppliesByCategory($supplies, $categories, $nowUtc8, $todayStr, $cutoffStr);
    }

    /**
     * Apply optional inventory index filters so the expiring container stays synced.
     */
    private function applyFilters(Builder $query, array $filters, ?User $user): void
    {
        $effectiveUser = $user ?? auth()->user();

        if (!empty($filters['division_id'])) {
            $query->where('division_id', $filters['division_id']);
        } elseif (!empty($filters['my_division_only']) && filter_var($filters['my_division_only'], FILTER_VALIDATE_BOOLEAN)) {
            if ($effectiveUser) {
                $query->where('division_id', $effectiveUser->division_id);
            }
        }

        if (!empty($filters['area_id'])) {
            $query->where('area_id', $filters['area_id']);
        } elseif (!empty($filters['my_area_only']) && filter_var($filters['my_area_only'], FILTER_VALIDATE_BOOLEAN)) {
            if ($effectiveUser) {
                $query->where('division_id', $effectiveUser->division_id)
                      ->where('area_id', $effectiveUser->area_id);
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
    }

    /**
     * Group expiring supplies by resolved category and calculate category/grand totals.
     */
    private function groupSuppliesByCategory(
        Collection $supplies,
        Collection $categories,
        Carbon $nowUtc8,
        string $todayStr,
        string $cutoffStr
    ): array {
        $groupedCategories = [];
        $totalItemsCount = 0;
        $grandTotalQty = 0;
        $grandTotalAmount = 0.0;

        foreach ($supplies as $item) {
            [$catCode, $catName] = $this->resolveCategory($item->category, $categories);

            $qty = (int) ($item->on_hand_per_count ?? 0);
            $unitVal = (float) ($item->unit_value ?? 0);
            $totalAmount = $item->total_amount !== null
                ? (float) $item->total_amount
                : round($qty * $unitVal, 2);

            $expiryStr = $item->expiry_date instanceof \DateTimeInterface
                ? $item->expiry_date->format('Y-m-d')
                : substr((string) $item->expiry_date, 0, 10);

            $expiryCarbon = Carbon::createFromFormat('Y-m-d', $expiryStr, '+08:00')->startOfDay();
            $daysUntilExpiry = (int) $nowUtc8->diffInDays($expiryCarbon, false);
            $isExpired = $expiryStr < $todayStr;

            if (!isset($groupedCategories[$catCode])) {
                $groupedCategories[$catCode] = [
                    'category_code' => $catCode,
                    'category_name' => $catName,
                    'items_count' => 0,
                    'total_quantity' => 0,
                    'total_amount' => 0.0,
                    'items' => [],
                ];
            }

            $groupedCategories[$catCode]['items_count']++;
            $groupedCategories[$catCode]['total_quantity'] += $qty;
            $groupedCategories[$catCode]['total_amount'] = round(
                $groupedCategories[$catCode]['total_amount'] + $totalAmount,
                2
            );

            $groupedCategories[$catCode]['items'][] = [
                'id' => $item->id,
                'article' => $item->article,
                'description' => $item->description,
                'stock_number' => $item->stock_number,
                'category' => $catCode,
                'category_name' => $catName,
                'unit_of_measure' => $item->unit_of_measure,
                'unit_value' => $unitVal,
                'on_hand_per_count' => $qty,
                'total_amount' => round($totalAmount, 2),
                'expiry_date' => $expiryStr,
                'days_until_expiry' => $daysUntilExpiry,
                'is_expired' => $isExpired,
                'status' => $item->status,
                'division_id' => $item->division_id,
                'division_name' => $item->division?->div_name,
                'area_id' => $item->area_id,
                'area_name' => $item->area?->area_name,
            ];

            $totalItemsCount++;
            $grandTotalQty += $qty;
            $grandTotalAmount += $totalAmount;
        }

        return [
            'total_items_count' => $totalItemsCount,
            'total_quantity' => $grandTotalQty,
            'total_amount' => round($grandTotalAmount, 2),
            'current_date_utc8' => $todayStr,
            'cutoff_date_utc8' => $cutoffStr,
            'categories' => array_values($groupedCategories),
        ];
    }

    /**
     * Resolve a raw supply category identifier to [code, name].
     *
     * @return array{0: string, 1: string}
     */
    private function resolveCategory(string|int|null $rawCategory, Collection $categories): array
    {
        $raw = trim((string) ($rawCategory ?? ''));
        if ($raw === '') {
            return ['uncategorized', 'Uncategorized'];
        }

        $lowerRaw = strtolower($raw);

        $match = $categories->first(fn(Category $cat) => strtolower((string) $cat->code) === $lowerRaw)
            ?? (is_numeric($raw) ? $categories->first(fn(Category $cat) => (string) $cat->id === $raw) : null)
            ?? $categories->first(fn(Category $cat) => strtolower((string) $cat->name) === $lowerRaw);

        if ($match) {
            return [(string) $match->code, (string) $match->name];
        }

        return [$raw, $raw];
    }

    /**
     * Build a consistent empty response structure.
     */
    private function emptyResult(string $todayStr, string $cutoffStr): array
    {
        return [
            'total_items_count' => 0,
            'total_quantity' => 0,
            'total_amount' => 0.0,
            'current_date_utc8' => $todayStr,
            'cutoff_date_utc8' => $cutoffStr,
            'categories' => [],
        ];
    }
}
