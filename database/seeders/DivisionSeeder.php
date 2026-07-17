<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            ['div_code' => 'HR', 'div_name' => 'Human Resources'],
            ['div_code' => 'IT', 'div_name' => 'Information Technology'],
            ['div_code' => 'NURSING', 'div_name' => 'Nursing'],
            ['div_code' => 'ADMIN', 'div_name' => 'Administration'],
            ['div_code' => 'FINANCE', 'div_name' => 'Finance'],
        ];

        foreach ($divisions as $dept) {
            Division::firstOrCreate(['div_code' => $dept['div_code']], $dept);
        }
    }
}
