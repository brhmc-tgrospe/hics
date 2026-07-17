<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Supplies\Models\Supply;

class DashboardController extends Controller
{
    public function index()
    {
        // For the dashboard, we probably want to send all the equipment and supplies, or at least the summaries.
        // The original React component expected all equipment and supplies arrays.
        // Depending on data size, this could be a bad idea, but since we are porting exactly what was there, we'll send it all for now.
        // If data size is huge, we should calculate these totals in the database and pass just the aggregates.
        $equipment = Equipment::all();
        $supplies = Supply::all();
        
        $equipmentCategories = \App\Domain\Shared\Models\Category::where('type', 'equipment')->get()->toArray();
        $supplyCategories = \App\Domain\Shared\Models\Category::where('type', 'supply')->get()->toArray();

        return Inertia::render('Dashboard/Index', [
            'equipment' => $equipment,
            'supplies' => $supplies,
            'equipmentCategories' => $equipmentCategories,
            'supplyCategories' => $supplyCategories,
        ]);
    }
}
