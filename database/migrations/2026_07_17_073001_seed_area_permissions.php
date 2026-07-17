<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_areas',
            'create_areas',
            'edit_areas',
            'delete_areas',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Give permissions to Admin and Superadmin
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        $superadminRole = Role::where('name', 'Superadmin')->first();
        if ($superadminRole) {
            $superadminRole->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_areas',
            'create_areas',
            'edit_areas',
            'delete_areas',
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::where('name', $permission)->first();
            if ($perm) {
                $perm->delete();
            }
        }
    }
};
