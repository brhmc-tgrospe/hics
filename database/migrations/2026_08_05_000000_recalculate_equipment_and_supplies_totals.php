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
        DB::statement('
            UPDATE equipment 
            SET shortage_overage_qty = (quantity_per_property_card - quantity_per_physical_count),
                shortage_overage_value = (quantity_per_property_card - quantity_per_physical_count) * unit_value,
                total_value = quantity_per_physical_count * unit_value
            WHERE unit_value IS NOT NULL
        ');

        DB::statement('
            UPDATE supplies 
            SET shortage_overage_qty = (balance_per_card - on_hand_per_count),
                shortage_overage_value = (balance_per_card - on_hand_per_count) * unit_value,
                total_amount = on_hand_per_count * unit_value
            WHERE unit_value IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: calculations are deterministic derived values
    }
};
