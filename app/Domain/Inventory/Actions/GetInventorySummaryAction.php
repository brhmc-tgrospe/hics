<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Supplies\Models\Supply;
use App\Domain\Inventory\DTOs\InventorySummaryDTO;
use Illuminate\Support\Facades\DB;

class GetInventorySummaryAction
{
    public function execute(): InventorySummaryDTO
    {
        $totalEquipment = (int) Equipment::sum('quantity_per_physical_count');
        $totalSupplies = (int) Supply::sum('on_hand_per_count');
        
        $equipmentValue = (float) Equipment::sum('total_value');
        $suppliesValue = (float) Supply::sum('total_amount');
        
        $equipmentByCategory = Equipment::select(DB::raw("COALESCE(category, 'Uncategorized') as category"), DB::raw('SUM(quantity_per_physical_count) as count'))
            ->groupBy(DB::raw("COALESCE(category, 'Uncategorized')"))
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
            
        $suppliesByCategory = Supply::select(DB::raw("COALESCE(category, 'Uncategorized') as category"), DB::raw('SUM(on_hand_per_count) as count'))
            ->groupBy(DB::raw("COALESCE(category, 'Uncategorized')"))
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        return new InventorySummaryDTO(
            $totalEquipment,
            $totalSupplies,
            $equipmentValue,
            $suppliesValue,
            $equipmentByCategory,
            $suppliesByCategory
        );
    }
}
