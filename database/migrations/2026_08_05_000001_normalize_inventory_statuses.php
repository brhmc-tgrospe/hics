<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalize Supplies status
        DB::table('supplies')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['available'])
            ->update(['status' => 'Available']);

        DB::table('supplies')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['depleted'])
            ->update(['status' => 'Depleted']);

        // Recalculate supplies totals where values exist
        DB::statement("
            UPDATE supplies
            SET shortage_overage_qty = balance_per_card - on_hand_per_count,
                shortage_overage_value = (balance_per_card - on_hand_per_count) * unit_value,
                total_amount = on_hand_per_count * unit_value
            WHERE balance_per_card IS NOT NULL 
              AND on_hand_per_count IS NOT NULL 
              AND unit_value IS NOT NULL
        ");

        // Normalize Equipment status
        DB::table('equipment')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['serviceable'])
            ->update(['status' => 'Serviceable']);

        DB::table('equipment')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['unserviceable'])
            ->update(['status' => 'Unserviceable']);

        // Recalculate equipment totals where values exist
        DB::statement("
            UPDATE equipment
            SET shortage_overage_qty = quantity_per_property_card - quantity_per_physical_count,
                shortage_overage_value = (quantity_per_property_card - quantity_per_physical_count) * unit_value,
                total_value = quantity_per_physical_count * unit_value
            WHERE quantity_per_property_card IS NOT NULL 
              AND quantity_per_physical_count IS NOT NULL 
              AND unit_value IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data normalization is irreversible and non-destructive.
    }
};
