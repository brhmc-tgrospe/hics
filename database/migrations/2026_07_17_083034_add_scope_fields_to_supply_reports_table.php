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
        Schema::table('supply_reports', function (Blueprint $table) {
            $table->string('report_type')->default('General');
            $table->string('report_period')->nullable();
            $table->integer('custom_month')->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_reports', function (Blueprint $table) {
            $table->dropColumn(['report_type', 'report_period', 'custom_month', 'scope_id']);
        });
    }
};
