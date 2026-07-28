<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\DashboardMetricsService;
use App\Domain\Shared\Models\Category;

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

        return Inertia::render('Dashboard/Index', [
            'aggregateMetrics' => $aggregateMetrics,
            'equipmentCategories' => $equipmentCategories,
            'supplyCategories' => $supplyCategories,
            'divisionTotals' => $divisionTotals,
            'discrepancyMetrics' => $discrepancyMetrics,
            'filters' => $request->only(['per_page_qty', 'per_page_value']),
        ]);
    }
}

