<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['dept_code' => 'HR', 'dept_name' => 'Human Resources'],
            ['dept_code' => 'IT', 'dept_name' => 'Information Technology'],
            ['dept_code' => 'NURSING', 'dept_name' => 'Nursing'],
            ['dept_code' => 'ADMIN', 'dept_name' => 'Administration'],
            ['dept_code' => 'FINANCE', 'dept_name' => 'Finance'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['dept_code' => $dept['dept_code']], $dept);
        }
    }
}
