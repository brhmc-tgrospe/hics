<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view_equipment',
            'create_equipment',
            'edit_equipment',
            'delete_equipment',
            
            'view_supplies',
            'create_supplies',
            'edit_supplies',
            'delete_supplies',
            
            'generate_reports',
            'view_activity_logs',
            'delete_activity_logs',
            'create_admin',

            // Users
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Divisions
            'view_divisions',
            'create_divisions',
            'edit_divisions',
            'delete_divisions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign created permissions
        
        // 1. Secretary
        $secretaryRole = Role::firstOrCreate(['name' => 'Secretary']);
        $secretaryRole->givePermissionTo([
            'view_equipment',
            'view_supplies',
            'generate_reports'
        ]);

        // 2. Encoder
        $encoderRole = Role::firstOrCreate(['name' => 'Encoder']);
        $encoderRole->givePermissionTo([
            'view_equipment', 'create_equipment', 'edit_equipment', 'delete_equipment',
            'view_supplies', 'create_supplies', 'edit_supplies', 'delete_supplies',
            'generate_reports'
        ]);

        // 3. Admin (Division Scoped)
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        // Admins can manage users within their division
        $adminRole->givePermissionTo(Permission::all());
        $adminRole->revokePermissionTo(['delete_activity_logs', 'create_admin', 'create_divisions', 'edit_divisions', 'delete_divisions']); // Admin has everything except these

        // 4. Superadmin (Can manage all Admins and below)
        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin']);
        $superadminRole->givePermissionTo(Permission::all());

        // 5. Developer (Gets all permissions via Gate::before in AuthServiceProvider)
        Role::firstOrCreate(['name' => 'Developer']);
    }
}
