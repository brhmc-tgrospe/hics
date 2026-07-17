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
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->string('article')->nullable();
            $table->text('description')->nullable();
            $table->string('stock_number')->nullable();
            $table->string('unit_of_measure')->nullable();
            $table->decimal('unit_value', 15, 2)->nullable();
            $table->integer('balance_per_card')->nullable();
            $table->integer('on_hand_per_count')->nullable();
            $table->integer('shortage_overage_qty')->nullable();
            $table->decimal('shortage_overage_value', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
