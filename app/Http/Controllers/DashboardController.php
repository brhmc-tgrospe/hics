<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Supplies\Models\Supply;
use App\Services\DashboardMetricsService;

class DashboardController extends Controller
{
    public function index(DashboardMetricsService $metricsService)
    {
        $user = auth()->user();
        $isSuper = $user->hasRole(['Developer', 'Superadmin']);
        
        if ($isSuper) {
            $equipment = Equipment::all();
            $supplies = Supply::all();
        } elseif ($user->division_id) {
            $equipment = Equipment::where('division_id', $user->division_id)->get();
            $supplies = Supply::where('division_id', $user->division_id)->get();
        } else {
            $equipment = collect();
            $supplies = collect();
        }

        
        $equipmentCategories = \App\Domain\Shared\Models\Category::where('type', 'equipment')->get()->toArray();
        $supplyCategories = \App\Domain\Shared\Models\Category::where('type', 'supply')->get()->toArray();

        $divisionTotals = $metricsService->getDivisionTotals(auth()->user());
        $discrepancyMetrics = $metricsService->getDiscrepancyMetrics(auth()->user());

        return Inertia::render('Dashboard/Index', [
            'equipment' => $equipment,
            'supplies' => $supplies,
            'equipmentCategories' => $equipmentCategories,
            'supplyCategories' => $supplyCategories,
            'divisionTotals' => $divisionTotals,
            'discrepancyMetrics' => $discrepancyMetrics,
        ]);
    }
}
