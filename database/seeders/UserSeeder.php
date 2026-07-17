<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Division;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $dept = Division::first();

        // Developer
        $developer = User::create([
            'first_name' => 'System',
            'last_name' => 'Developer',
            'username' => 'sysdev',
            'contact_number' => '09000000001',
            'email' => 'dev@hics.local',
            'password' => Hash::make('password123'),
            'division_id' => clone $dept ? $dept->id : null,
        ]);
        $developer->assignRole('Developer');

        // Superadmin
        $superadmin = User::create([
            'first_name' => 'System',
            'last_name' => 'Superadmin',
            'username' => 'superadmin',
            'contact_number' => '09000000002',
            'email' => 'superadmin@hics.local',
            'password' => Hash::make('password'),
            'division_id' => clone $dept ? $dept->id : null,
        ]);
        $superadmin->assignRole('Superadmin');

        // Admin
        $admin = User::create([
            'first_name' => 'System',
            'last_name' => 'Admin',
            'username' => 'admin',
            'contact_number' => '09000000003',
            'email' => 'admin@hics.local',
            'password' => Hash::make('password'),
            'division_id' => clone $dept ? $dept->id : null,
        ]);
        $admin->assignRole('Admin');

        // Encoder
        $encoder = User::create([
            'first_name' => 'Data',
            'last_name' => 'Encoder',
            'username' => 'encoder',
            'contact_number' => '09000000004',
            'email' => 'encoder@hics.local',
            'password' => Hash::make('password'),
            'division_id' => clone $dept ? $dept->id : null,
        ]);
        $encoder->assignRole('Encoder');

        // Secretary
        $secretary = User::create([
            'first_name' => 'Office',
            'last_name' => 'Secretary',
            'username' => 'secretary',
            'contact_number' => '09000000005',
            'email' => 'secretary@hics.local',
            'password' => Hash::make('password'),
            'division_id' => clone $dept ? $dept->id : null,
        ]);
        $secretary->assignRole('Secretary');
    }
}
