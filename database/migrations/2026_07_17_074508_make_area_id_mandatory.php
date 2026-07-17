<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create default area for each division
        $divisions = DB::table('divisions')->get();
        foreach ($divisions as $division) {
            $areaId = DB::table('areas')->where('division_id', $division->id)->value('id');
            if (!$areaId) {
                $areaId = DB::table('areas')->insertGetId([
                    'area_name' => 'General Area',
                    'division_id' => $division->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 2. Assign area to items that have this division_id but no area_id
            DB::table('users')->where('division_id', $division->id)->whereNull('area_id')->update(['area_id' => $areaId]);
            DB::table('equipment')->where('division_id', $division->id)->whereNull('area_id')->update(['area_id' => $areaId]);
            DB::table('supplies')->where('division_id', $division->id)->whereNull('area_id')->update(['area_id' => $areaId]);
        }

        // For records with NO division_id (e.g. Superadmin), we'll let area_id remain null if allowed, 
        // wait, we are making area_id NOT NULL for ALL records.
        // For users who are Superadmin, do they have division_id = null?
        // Let's check if there are users with division_id = null.
        $superAdminAreaId = null;
        if (DB::table('users')->whereNull('division_id')->exists() || DB::table('users')->whereNull('area_id')->exists()) {
            $superAdminAreaId = DB::table('areas')->where('area_name', 'System Admin Area')->value('id');
            if (!$superAdminAreaId) {
                // Ensure there's a division for them? 
                // Let's create a generic division if needed.
                $adminDivId = DB::table('divisions')->where('div_name', 'System Admin')->value('id');
                if (!$adminDivId) {
                    $adminDivId = DB::table('divisions')->insertGetId([
                        'div_name' => 'System Admin',
                        'div_code' => 'SYSADMIN',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $superAdminAreaId = DB::table('areas')->insertGetId([
                    'area_name' => 'System Admin Area',
                    'division_id' => $adminDivId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('users')->whereNull('area_id')->update(['division_id' => $adminDivId ?? DB::table('areas')->where('id', $superAdminAreaId)->value('division_id'), 'area_id' => $superAdminAreaId]);
            DB::table('equipment')->whereNull('area_id')->update(['division_id' => $adminDivId ?? DB::table('areas')->where('id', $superAdminAreaId)->value('division_id'), 'area_id' => $superAdminAreaId]);
            DB::table('supplies')->whereNull('area_id')->update(['division_id' => $adminDivId ?? DB::table('areas')->where('id', $superAdminAreaId)->value('division_id'), 'area_id' => $superAdminAreaId]);
        }

        // 3. Make area_id NOT NULL and recreate foreign keys
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable(false)->change();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('restrict');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
        });
        Schema::table('equipment', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable(false)->change();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('restrict');
        });

        Schema::table('supplies', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
        });
        Schema::table('supplies', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable(false)->change();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable()->change();
        });
        Schema::table('equipment', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable()->change();
        });
        Schema::table('supplies', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable()->change();
        });
    }
};
