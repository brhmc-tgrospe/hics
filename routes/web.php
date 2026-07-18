<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CategoryController;

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Reports Routes
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index')->middleware('permission:generate_reports');
    Route::post('reports/bulk-delete', [ReportController::class, 'destroyMultiple'])->name('reports.bulk_delete')->middleware('permission:generate_reports');

    // Equipment Routes
    Route::get('equipment/template', [EquipmentController::class, 'template'])->name('equipment.template')->middleware('permission:create_equipment');
    Route::post('equipment/import', [EquipmentController::class, 'import'])->name('equipment.import')->middleware('permission:create_equipment');
    Route::post('equipment/report', [EquipmentController::class, 'generateReport'])->name('equipment.report.generate')->middleware('permission:generate_reports');
    Route::get('equipment/report/{id}', [EquipmentController::class, 'showReport'])->name('equipment.report.show')->middleware('permission:generate_reports');
    Route::delete('equipment/bulk-delete', [EquipmentController::class, 'bulkDestroy'])->name('equipment.bulk_delete')->middleware('permission:delete_equipment');
    
    Route::resource('equipment', EquipmentController::class)->except(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('equipment', [EquipmentController::class, 'index'])->name('equipment.index')->middleware('permission:view_equipment');
    Route::get('equipment/create', [EquipmentController::class, 'create'])->name('equipment.create')->middleware('permission:create_equipment');
    Route::post('equipment', [EquipmentController::class, 'store'])->name('equipment.store')->middleware('permission:create_equipment');
    Route::get('equipment/{equipment}', [EquipmentController::class, 'show'])->name('equipment.show')->middleware('permission:view_equipment');
    Route::get('equipment/{equipment}/edit', [EquipmentController::class, 'edit'])->name('equipment.edit')->middleware('permission:edit_equipment');
    Route::put('equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update')->middleware('permission:edit_equipment');
    Route::delete('equipment/{equipment}', [EquipmentController::class, 'destroy'])->name('equipment.destroy')->middleware('permission:delete_equipment');

    // Supplies Routes
    Route::get('supplies/template', [SupplyController::class, 'template'])->name('supplies.template')->middleware('permission:create_supplies');
    Route::post('supplies/import', [SupplyController::class, 'import'])->name('supplies.import')->middleware('permission:create_supplies');
    Route::post('supplies/report', [SupplyController::class, 'generateReport'])->name('supplies.report.generate')->middleware('permission:generate_reports');
    Route::get('supplies/report/{id}', [SupplyController::class, 'showReport'])->name('supplies.report.show')->middleware('permission:generate_reports');
    Route::delete('supplies/bulk-delete', [SupplyController::class, 'bulkDestroy'])->name('supplies.bulk_delete')->middleware('permission:delete_supplies');
    
    Route::resource('supplies', SupplyController::class)->except(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('supplies', [SupplyController::class, 'index'])->name('supplies.index')->middleware('permission:view_supplies');
    Route::get('supplies/create', [SupplyController::class, 'create'])->name('supplies.create')->middleware('permission:create_supplies');
    Route::post('supplies', [SupplyController::class, 'store'])->name('supplies.store')->middleware('permission:create_supplies');
    Route::get('supplies/{supply}', [SupplyController::class, 'show'])->name('supplies.show')->middleware('permission:view_supplies');
    Route::get('supplies/{supply}/edit', [SupplyController::class, 'edit'])->name('supplies.edit')->middleware('permission:edit_supplies');
    Route::put('supplies/{supply}', [SupplyController::class, 'update'])->name('supplies.update')->middleware('permission:edit_supplies');
    Route::delete('supplies/{supply}', [SupplyController::class, 'destroy'])->name('supplies.destroy')->middleware('permission:delete_supplies');

    // User Routes
    Route::delete('users/bulk-delete', [UserController::class, 'bulkDestroy'])->name('users.bulk_delete')->middleware('permission:delete_users');
    Route::get('users', [UserController::class, 'index'])->name('users.index')->middleware('permission:view_users');
    Route::post('users', [UserController::class, 'store'])->name('users.store')->middleware('permission:create_users');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('permission:edit_users');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('permission:delete_users');
    
    // Division Routes
    Route::delete('divisions/bulk-delete', [DivisionController::class, 'bulkDestroy'])->name('divisions.bulk_delete')->middleware('permission:delete_divisions');
    Route::get('divisions', [DivisionController::class, 'index'])->name('divisions.index')->middleware('permission:view_divisions');
    Route::post('divisions', [DivisionController::class, 'store'])->name('divisions.store')->middleware('permission:create_divisions');
    Route::put('divisions/{division}', [DivisionController::class, 'update'])->name('divisions.update')->middleware('permission:edit_divisions');
    Route::delete('divisions/{division}', [DivisionController::class, 'destroy'])->name('divisions.destroy')->middleware('permission:delete_divisions');
    
    // Area Routes
    Route::delete('areas/bulk-delete', [\App\Http\Controllers\AreaController::class, 'bulkDestroy'])->name('areas.bulk_delete')->middleware('permission:delete_areas');
    Route::get('areas', [\App\Http\Controllers\AreaController::class, 'index'])->name('areas.index')->middleware('permission:view_areas');
    Route::post('areas', [\App\Http\Controllers\AreaController::class, 'store'])->name('areas.store')->middleware('permission:create_areas');
    Route::put('areas/{area}', [\App\Http\Controllers\AreaController::class, 'update'])->name('areas.update')->middleware('permission:edit_areas');
    Route::delete('areas/{area}', [\App\Http\Controllers\AreaController::class, 'destroy'])->name('areas.destroy')->middleware('permission:delete_areas');
    
    // Impersonation Routes
    Route::get('impersonate/leave', [ImpersonationController::class, 'leave'])->name('impersonate.leave');
    Route::get('impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');

    // Profile Routes
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    // Activity Logs Routes
    Route::get('activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index')->middleware('permission:view_activity_logs');
    Route::delete('activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'destroy'])->name('activity-logs.destroy')->middleware('permission:delete_activity_logs');

    // Category Routes
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::delete('categories/bulk-delete', [CategoryController::class, 'bulkDestroy'])->name('categories.bulk_delete');
});

require __DIR__.'/auth.php';
