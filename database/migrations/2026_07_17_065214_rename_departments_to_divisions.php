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
        // Drop foreign keys
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        // Rename table and columns
        Schema::rename('departments', 'divisions');
        
        Schema::table('divisions', function (Blueprint $table) {
            $table->renameColumn('dept_code', 'div_code');
            $table->renameColumn('dept_name', 'div_name');
        });

        // Rename referencing columns
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('department_id', 'division_id');
        });
        Schema::table('equipment', function (Blueprint $table) {
            $table->renameColumn('department_id', 'division_id');
        });
        Schema::table('supplies', function (Blueprint $table) {
            $table->renameColumn('department_id', 'division_id');
        });

        // Re-add foreign keys
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
        });
        Schema::table('equipment', function (Blueprint $table) {
            $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
        });
        Schema::table('supplies', function (Blueprint $table) {
            $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
        });
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
        });
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('division_id', 'department_id');
        });
        Schema::table('equipment', function (Blueprint $table) {
            $table->renameColumn('division_id', 'department_id');
        });
        Schema::table('supplies', function (Blueprint $table) {
            $table->renameColumn('division_id', 'department_id');
        });

        Schema::table('divisions', function (Blueprint $table) {
            $table->renameColumn('div_code', 'dept_code');
            $table->renameColumn('div_name', 'dept_name');
        });

        Schema::rename('divisions', 'departments');

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
        Schema::table('equipment', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
        Schema::table('supplies', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }
};
