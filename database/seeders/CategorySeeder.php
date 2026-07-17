<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $equipment = [
            ['code' => 'fandf', 'name' => 'FURNITURE AND FIXTURES', 'type' => 'equipment'],
            ['code' => 'othermach', 'name' => 'OTHER MACHINERIES AND EQUIPMENT', 'type' => 'equipment'],
            ['code' => 'comms', 'name' => 'COMMUNICATION EQUIPMENT', 'type' => 'equipment'],
            ['code' => 'sports', 'name' => 'SPORTS EQUIPMENT', 'type' => 'equipment'],
            ['code' => 'mv', 'name' => 'MOTOR VEHICLES', 'type' => 'equipment'],
            ['code' => 'hhcenter', 'name' => 'HOSPITAL & HEALTH CENTER BUILDING', 'type' => 'equipment'],
            ['code' => 'bldg', 'name' => 'BUILDINGS AND STRUCTURES', 'type' => 'equipment'],
            ['code' => 'office', 'name' => 'OFFICE EQUIPMENT', 'type' => 'equipment'],
            ['code' => 'ictequip', 'name' => 'INFORMATION AND COMMUNICATION TECHNOLOGY EQUIPMENT', 'type' => 'equipment'],
            ['code' => 'medical', 'name' => 'MEDICAL EQUIPMENT', 'type' => 'equipment'],
        ];

        $supplies = [
            ['code' => 'ictsupply', 'name' => 'INFORMATION AND COMMUNICATION TECHNOLOGY SUPPLIES', 'type' => 'supply'],
            ['code' => 'mssup', 'name' => 'MEDICAL AND SURGICAL SUPPLIES', 'type' => 'supply'],
            ['code' => 'office', 'name' => 'OFFICE SUPPLIES', 'type' => 'supply'],
            ['code' => 'enteral', 'name' => 'ENTERAL SUPPLIES', 'type' => 'supply'],
            ['code' => 'hksupp', 'name' => 'HOUSEKEEPING SUPPLIES', 'type' => 'supply'],
        ];

        foreach (array_merge($equipment, $supplies) as $category) {
            \App\Domain\Shared\Models\Category::firstOrCreate(
                ['code' => $category['code'], 'type' => $category['type']],
                ['name' => $category['name']]
            );
        }
    }
}
