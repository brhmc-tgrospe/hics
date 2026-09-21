<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Area;
use App\Models\Division;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Models\EquipmentReport;
use App\Domain\Supplies\Models\Supply;
use App\Domain\Supply\Models\SupplyReport;
use App\Domain\Equipment\Actions\GetEquipmentReportDataAction;
use App\Domain\Supplies\Actions\GetSupplyReportDataAction;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportSortingTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;
    protected Area $area1;
    protected Area $area2;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'Superadmin']);
        Permission::firstOrCreate(['name' => 'generate_reports']);

        $this->division = Division::create(['div_code' => 'MED', 'div_name' => 'Medical Division']);

        $this->area1 = Area::create([
            'area_name' => 'Emergency Room',
            'area_code' => 'ER',
            'division_id' => $this->division->id,
        ]);

        $this->area2 = Area::create([
            'area_name' => 'Intensive Care Unit',
            'area_code' => 'ICU',
            'division_id' => $this->division->id,
        ]);

        $this->user = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->area1->id,
        ]);
        $this->user->assignRole('Superadmin');
        $this->user->givePermissionTo('generate_reports');
    }

    public function test_equipment_general_report_generation_sorts_alphabetically_across_areas(): void
    {
        // Deliberately create items in reverse/mixed order across two different areas
        Equipment::create([
            'category' => 'medequip',
            'article' => 'Zebra Scanner',
            'description' => 'Model Z',
            'division_id' => $this->division->id,
            'area_id' => $this->area1->id,
        ]);

        Equipment::create([
            'category' => 'medequip',
            'article' => 'Beta Monitor',
            'description' => 'Screen 2',
            'division_id' => $this->division->id,
            'area_id' => $this->area1->id,
        ]);

        Equipment::create([
            'category' => 'medequip',
            'article' => 'Beta Monitor',
            'description' => 'Screen 1',
            'division_id' => $this->division->id,
            'area_id' => $this->area2->id,
        ]);

        Equipment::create([
            'category' => 'medequip',
            'article' => 'Alpha Cart',
            'description' => 'Stainless steel',
            'division_id' => $this->division->id,
            'area_id' => $this->area2->id,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('equipment.report.generate'), [
            'category' => 'medequip',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'report_type' => 'General',
            'scope_id' => null,
        ]);

        $response->assertOk();
        $reportId = $response->json('id');
        $report = EquipmentReport::findOrFail($reportId);

        $savedContent = json_decode(Storage::disk('local')->get($report->file_path), true);

        $this->assertCount(4, $savedContent);
        $this->assertEquals('Alpha Cart', $savedContent[0]['article']);
        $this->assertEquals('Beta Monitor', $savedContent[1]['article']);
        $this->assertEquals('Screen 1', $savedContent[1]['description']);
        $this->assertEquals('Beta Monitor', $savedContent[2]['article']);
        $this->assertEquals('Screen 2', $savedContent[2]['description']);
        $this->assertEquals('Zebra Scanner', $savedContent[3]['article']);
    }

    public function test_supply_general_report_generation_sorts_alphabetically_across_areas(): void
    {
        Supply::create([
            'category' => 'medsupply',
            'article' => 'Zinc Oxide Ointment',
            'description' => '50g Tube',
            'division_id' => $this->division->id,
            'area_id' => $this->area1->id,
        ]);

        Supply::create([
            'category' => 'medsupply',
            'article' => 'Bandage Elastic',
            'description' => 'Size Large',
            'division_id' => $this->division->id,
            'area_id' => $this->area1->id,
        ]);

        Supply::create([
            'category' => 'medsupply',
            'article' => 'Bandage Elastic',
            'description' => 'Size Medium',
            'division_id' => $this->division->id,
            'area_id' => $this->area2->id,
        ]);

        Supply::create([
            'category' => 'medsupply',
            'article' => 'Alcohol 70%',
            'description' => '500ml bottle',
            'division_id' => $this->division->id,
            'area_id' => $this->area2->id,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('supplies.report.generate'), [
            'category' => 'medsupply',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'report_type' => 'General',
            'scope_id' => null,
        ]);

        $response->assertOk();
        $reportId = $response->json('id');
        $report = SupplyReport::findOrFail($reportId);

        $savedContent = json_decode(Storage::disk('local')->get($report->file_path), true);

        $this->assertCount(4, $savedContent);
        $this->assertEquals('Alcohol 70%', $savedContent[0]['article']);
        $this->assertEquals('Bandage Elastic', $savedContent[1]['article']);
        $this->assertEquals('Size Large', $savedContent[1]['description']);
        $this->assertEquals('Bandage Elastic', $savedContent[2]['article']);
        $this->assertEquals('Size Medium', $savedContent[2]['description']);
        $this->assertEquals('Zinc Oxide Ointment', $savedContent[3]['article']);
    }

    public function test_get_equipment_report_data_action_sorts_historical_snapshots(): void
    {
        $unsortedData = [
            ['id' => 1, 'article' => 'Stethoscope', 'description' => 'Classic III'],
            ['id' => 2, 'article' => 'Autoclave', 'description' => 'Horizontal'],
            ['id' => 3, 'article' => 'Defibrillator', 'description' => 'Biphasic'],
            ['id' => 4, 'article' => 'autoclave', 'description' => 'portable'],
        ];

        $filePath = 'reports/test_historical_equipment.json';
        Storage::disk('local')->put($filePath, json_encode($unsortedData));

        $report = EquipmentReport::create([
            'category' => 'medequip',
            'date_of_accountability' => '2026-01-01',
            'year_of_report' => 2026,
            'file_path' => $filePath,
            'report_type' => 'General',
            'user_id' => $this->user->id,
        ]);

        $action = new GetEquipmentReportDataAction();
        $result = $action->execute($report);

        $this->assertCount(4, $result);
        $this->assertEquals('Autoclave', $result[0]['article']);
        $this->assertEquals('Horizontal', $result[0]['description']);
        $this->assertEquals('autoclave', $result[1]['article']);
        $this->assertEquals('portable', $result[1]['description']);
        $this->assertEquals('Defibrillator', $result[2]['article']);
        $this->assertEquals('Stethoscope', $result[3]['article']);
    }

    public function test_get_supply_report_data_action_sorts_historical_snapshots(): void
    {
        $unsortedData = [
            ['id' => 1, 'article' => 'Syringe 10ml', 'description' => 'Sterile'],
            ['id' => 2, 'article' => 'Cotton Balls', 'description' => 'Pack of 100'],
            ['id' => 3, 'article' => 'Alcohol Pads', 'description' => 'Box of 200'],
        ];

        $filePath = 'reports/test_historical_supplies.json';
        Storage::disk('local')->put($filePath, json_encode($unsortedData));

        $report = SupplyReport::create([
            'category' => 'medsupply',
            'date_of_accountability' => '2026-01-01',
            'year_of_report' => 2026,
            'file_path' => $filePath,
            'report_type' => 'General',
            'user_id' => $this->user->id,
        ]);

        $action = new GetSupplyReportDataAction();
        $result = $action->execute($report);

        $this->assertCount(3, $result);
        $this->assertEquals('Alcohol Pads', $result[0]['article']);
        $this->assertEquals('Cotton Balls', $result[1]['article']);
        $this->assertEquals('Syringe 10ml', $result[2]['article']);
    }

    public function test_reconstruction_fallback_sorts_alphabetically(): void
    {
        Equipment::create([
            'category' => 'medequip',
            'article' => 'X-Ray Machine',
            'description' => 'Digital',
            'division_id' => $this->division->id,
            'area_id' => $this->area1->id,
        ]);

        Equipment::create([
            'category' => 'medequip',
            'article' => 'Anesthesia Machine',
            'description' => 'Workstation',
            'division_id' => $this->division->id,
            'area_id' => $this->area2->id,
        ]);

        $report = EquipmentReport::create([
            'category' => 'medequip',
            'date_of_accountability' => '2026-01-01',
            'year_of_report' => 2026,
            'file_path' => 'reports/non_existent.json',
            'report_type' => 'General',
            'user_id' => $this->user->id,
        ]);

        $action = new GetEquipmentReportDataAction();
        $result = $action->execute($report);

        $this->assertCount(2, $result);
        $this->assertEquals('Anesthesia Machine', $result[0]['article']);
        $this->assertEquals('X-Ray Machine', $result[1]['article']);
    }
}
