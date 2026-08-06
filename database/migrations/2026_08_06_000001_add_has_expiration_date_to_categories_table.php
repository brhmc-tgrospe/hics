<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('categories', 'has_expiration_date')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->boolean('has_expiration_date')->default(false)->after('type');
            });
        }

        // Set has_expiration_date = true for medical, enteral, drugs, and food categories
        DB::table('categories')
            ->where('type', 'supply')
            ->where(function ($query) {
                $query->whereIn('code', ['mssup', 'enteral', 'drmeds', 'foodsupplies'])
                      ->orWhere('name', 'like', '%food%')
                      ->orWhereIn('name', [
                          'Medical and Surgical Supplies',
                          'Enteral Supplies',
                          'Drugs and Medicines',
                          'Food Supplies',
                      ]);
            })
            ->where('name', 'not like', '%non-food%')
            ->where('name', 'not like', '%nonfood%')
            ->update(['has_expiration_date' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('categories', 'has_expiration_date')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('has_expiration_date');
            });
        }
    }
};
