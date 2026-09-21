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
        DB::table('divisions')
            ->where('head_first_name', 'Anna Lynda')
            ->update([
                'head_last_name' => 'Bellen',
                'head_nominal_letters' => 'MD, MMHoA, FPCP, FPSMID',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal necessary or revert 13m if needed
    }
};
