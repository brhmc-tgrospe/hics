<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'categories', 'equipment', 'supplies', 'equipment_reports',
            'supply_reports', 'users', 'divisions', 'areas'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_at');
                $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'categories', 'equipment', 'supplies', 'equipment_reports',
            'supply_reports', 'users', 'divisions', 'areas'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn('deleted_by');
            });
        }
    }
};
