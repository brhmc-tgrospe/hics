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
        Schema::table('divisions', function (Blueprint $table) {
            $table->string('head_first_name')->nullable();
            $table->string('head_middle_initial', 1)->nullable();
            $table->string('head_last_name')->nullable();
            $table->string('head_nominal_letters')->nullable();
            $table->string('head_designation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropColumn([
                'head_first_name',
                'head_middle_initial',
                'head_last_name',
                'head_nominal_letters',
                'head_designation'
            ]);
        });
    }
};
