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
        DB::table('equipment')
            ->where('category', 'medequip')
            ->update(['category' => 'medical']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op or revert if necessary
    }
};
