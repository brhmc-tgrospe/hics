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
        
        $perPage = $request->input('per_page', 5);
        $discrepancyMetrics = $metricsService->getDiscrepancyMetrics($user, $perPage);

        return Inertia::render('Dashboard/Index', [
            'aggregateMetrics' => $aggregateMetrics,
            'equipmentCategories' => $equipmentCategories,
            'supplyCategories' => $supplyCategories,
            'divisionTotals' => $divisionTotals,
            'discrepancyMetrics' => $discrepancyMetrics,
            'filters' => $request->only(['per_page']),
        ]);
    }
}

