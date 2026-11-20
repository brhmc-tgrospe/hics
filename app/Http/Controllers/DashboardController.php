<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\DashboardMetricsService;
use App\Domain\Shared\Models\Category;
use App\Domain\Supplies\Actions\GetExpiringSuppliesAction;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardMetricsService $metricsService)
    {
        $user = auth()->user();
        
        $aggregateMetrics = $metricsService->getAggregateMetrics($user);
        
        $equipmentCategories = Category::where('type', 'equipment')->get()->toArray();
        $supplyCategories = Category::where('type', 'supply')->get()->toArray();

        $divisionTotals = $metricsService->getDivisionTotals($user);
        
        $perPageQty = $request->input('per_page_qty', 5);
        $perPageValue = $request->input('per_page_value', 5);
        $discrepancyMetrics = $metricsService->getDiscrepancyMetrics($user, $perPageQty, $perPageValue);
        $expiringSupplies = app(GetExpiringSuppliesAction::class)->execute($user, [], true);
        $divisions = \App\Models\Division::select('id', 'div_name as name')->get()->toArray();
        $areas = \App\Models\Area::select('id', 'area_name as name', 'division_id')->get()->toArray();

        return Inertia::render('Dashboard/Index', [
            'aggregateMetrics' => $aggregateMetrics,
            'equipmentCategories' => $equipmentCategories,
            'supplyCategories' => $supplyCategories,
            'divisionTotals' => $divisionTotals,
            'discrepancyMetrics' => $discrepancyMetrics,
            'expiringSupplies' => $expiringSupplies,
            'divisions' => $divisions,
            'areas' => $areas,
            'filters' => $request->only(['per_page_qty', 'per_page_value']),
        ]);
    }
}

