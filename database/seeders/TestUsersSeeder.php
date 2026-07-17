<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Division;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            'Information Technology' => 'it',
            'Nursing' => 'nursing',
            'Administration' => 'admin',
            'Finance' => 'finance'
        ];

        $roles = ['Admin', 'Encoder', 'Secretary'];
        
        // Ensure roles exist
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $password = Hash::make('password'); // Default password

        foreach ($divisions as $deptName => $deptPrefix) {
            $division = Division::where('div_name', $deptName)->first();
            
            if (!$division) {
                $this->command->warn("Division {$deptName} not found.");
                continue;
            }

            foreach ($roles as $roleName) {
                $rolePrefix = strtolower($roleName);
                $email = "{$rolePrefix}.{$deptPrefix}@test.com";
                $username = "{$rolePrefix}_{$deptPrefix}";

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'first_name' => "{$deptName}",
                        'last_name' => "{$roleName}",
                        'username' => $username,
                        'division_id' => $division->id,
                        'password' => $password,
                        'email_verified_at' => now(),
                        'remember_token' => Str::random(10),
                    ]
                );

                // Assign role
                if (!$user->hasRole($roleName)) {
                    $user->assignRole($roleName);
                }
            }
        }
        
        $this->command->info("Test users created for divisions: " . implode(', ', array_keys($divisions)));
    }
}
