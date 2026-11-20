<?php

namespace Tests\Feature;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Models\EquipmentReport;
use App\Domain\Supplies\Models\Supply;
use App\Domain\Supply\Models\SupplyReport;
use App\Models\Area;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SecretaryRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;
    protected Division $otherDivision;
    protected Area $generalArea;
    protected Area $area;
    protected Area $otherArea;
    protected User $secretary;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->division = Division::create(['div_code' => 'MED', 'div_name' => 'Medical Division']);
        $this->otherDivision = Division::create(['div_code' => 'ADM', 'div_name' => 'Administrative Division']);

        $this->generalArea = Area::create([
            'area_name' => 'General Area',
            'area_code' => 'GEN',
            'division_id' => $this->division->id,
        ]);

        $this->area = Area::create([
            'area_name' => 'Emergency Room',
            'area_code' => 'ER',
            'division_id' => $this->division->id,
        ]);

        $this->otherArea = Area::create([
            'area_name' => 'HR Office',
            'area_code' => 'HR',
            'division_id' => $this->otherDivision->id,
        ]);

        $this->secretary = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $this->secretary->assignRole('Secretary');

        $this->otherUser = User::factory()->create([
            'division_id' => $this->otherDivision->id,
            'area_id' => $this->otherArea->id,
        ]);
        $this->otherUser->assignRole('Admin');
    }

    public function test_secretary_can_generate_equipment_reports_across_divisions_and_areas(): void
    {
        Equipment::create([
            'category' => 'medical',
            'article' => 'ER Monitor',
            'description' => 'Patient monitor',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        Equipment::create([
            'category' => 'medical',
            'article' => 'Admin Scanner',
            'description' => 'Office scanner',
            'division_id' => $this->otherDivision->id,
            'area_id' => $this->otherArea->id,
        ]);

        // General report
        $generalResponse = $this->actingAs($this->secretary)->postJson(route('equipment.report.generate'), [
            'category' => 'medical',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'report_type' => 'General',
            'scope_id' => null,
        ]);
        $generalResponse->assertOk();
        $generalReport = EquipmentReport::findOrFail($generalResponse->json('id'));
        $this->assertCount(2, json_decode(Storage::disk('local')->get($generalReport->file_path), true));

        // Other division report
        $divisionResponse = $this->actingAs($this->secretary)->postJson(route('equipment.report.generate'), [
            'category' => 'medical',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'report_type' => 'Division',
            'scope_id' => $this->otherDivision->id,
        ]);
        $divisionResponse->assertOk();
        $divisionReport = EquipmentReport::findOrFail($divisionResponse->json('id'));
        $divisionItems = json_decode(Storage::disk('local')->get($divisionReport->file_path), true);
        $this->assertCount(1, $divisionItems);
        $this->assertEquals('Admin Scanner', $divisionItems[0]['article']);

        // Other area report
        $areaResponse = $this->actingAs($this->secretary)->postJson(route('equipment.report.generate'), [
            'category' => 'medical',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'report_type' => 'Area',
            'scope_id' => $this->otherArea->id,
        ]);
        $areaResponse->assertOk();
        $areaReport = EquipmentReport::findOrFail($areaResponse->json('id'));
        $areaItems = json_decode(Storage::disk('local')->get($areaReport->file_path), true);
        $this->assertCount(1, $areaItems);
        $this->assertEquals('Admin Scanner', $areaItems[0]['article']);
    }

    public function test_secretary_can_generate_supply_reports_across_divisions_and_areas(): void
    {
        Supply::create([
            'category' => 'medsupply',
            'article' => 'ER Bandage',
            'description' => 'Sterile bandage',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        Supply::create([
            'category' => 'medsupply',
            'article' => 'Admin Gloves',
            'description' => 'Box of gloves',
            'division_id' => $this->otherDivision->id,
            'area_id' => $this->otherArea->id,
        ]);

        // General report
        $generalResponse = $this->actingAs($this->secretary)->postJson(route('supplies.report.generate'), [
            'category' => 'medsupply',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'report_type' => 'General',
            'scope_id' => null,
        ]);
        $generalResponse->assertOk();
        $generalReport = SupplyReport::findOrFail($generalResponse->json('id'));
        $this->assertCount(2, json_decode(Storage::disk('local')->get($generalReport->file_path), true));

        // Other division report
        $divisionResponse = $this->actingAs($this->secretary)->postJson(route('supplies.report.generate'), [
            'category' => 'medsupply',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'report_type' => 'Division',
            'scope_id' => $this->otherDivision->id,
        ]);
        $divisionResponse->assertOk();
        $divisionReport = SupplyReport::findOrFail($divisionResponse->json('id'));
        $divisionItems = json_decode(Storage::disk('local')->get($divisionReport->file_path), true);
        $this->assertCount(1, $divisionItems);
        $this->assertEquals('Admin Gloves', $divisionItems[0]['article']);

        // Other area report
        $areaResponse = $this->actingAs($this->secretary)->postJson(route('supplies.report.generate'), [
            'category' => 'medsupply',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'report_type' => 'Area',
            'scope_id' => $this->otherArea->id,
        ]);
        $areaResponse->assertOk();
        $areaReport = SupplyReport::findOrFail($areaResponse->json('id'));
        $areaItems = json_decode(Storage::disk('local')->get($areaReport->file_path), true);
        $this->assertCount(1, $areaItems);
        $this->assertEquals('Admin Gloves', $areaItems[0]['article']);
    }

    public function test_cannot_generate_area_report_for_general_area(): void
    {
        $eqResponse = $this->actingAs($this->secretary)->post(route('equipment.report.generate'), [
            'category' => 'medical',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'report_type' => 'Area',
            'scope_id' => $this->generalArea->id,
        ]);
        $eqResponse->assertSessionHasErrors('scope_id');
        $this->assertDatabaseCount('equipment_reports', 0);

        $supResponse = $this->actingAs($this->secretary)->post(route('supplies.report.generate'), [
            'category' => 'medsupply',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'report_type' => 'Area',
            'scope_id' => $this->generalArea->id,
        ]);
        $supResponse->assertSessionHasErrors('scope_id');
        $this->assertDatabaseCount('supply_reports', 0);
    }

    public function test_secretary_can_view_all_reports_across_divisions_and_areas_without_delete_permission(): void
    {
        $this->withoutVite();

        EquipmentReport::create([
            'category' => 'medical',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'file_path' => 'reports/eq_general_other.json',
            'report_type' => 'General',
            'scope_id' => null,
            'user_id' => $this->otherUser->id,
        ]);

        EquipmentReport::create([
            'category' => 'medical',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'file_path' => 'reports/eq_div_other.json',
            'report_type' => 'Division',
            'scope_id' => $this->otherDivision->id,
            'user_id' => $this->otherUser->id,
        ]);

        SupplyReport::create([
            'category' => 'medsupply',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'file_path' => 'reports/sup_area_other.json',
            'report_type' => 'Area',
            'scope_id' => $this->otherArea->id,
            'user_id' => $this->otherUser->id,
        ]);

        SupplyReport::create([
            'category' => 'medsupply',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'file_path' => 'reports/sup_general_sec.json',
            'report_type' => 'General',
            'scope_id' => null,
            'user_id' => $this->secretary->id,
        ]);

        $response = $this->actingAs($this->secretary)->get(route('reports.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->has('reports.data', 4)
            ->where('reports.data', function ($reports) {
                return collect($reports)->every(fn ($report) => $report['can_delete'] === false);
            })
        );
    }

    public function test_secretary_cannot_delete_any_reports(): void
    {
        $ownGeneralReport = EquipmentReport::create([
            'category' => 'medical',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'file_path' => 'reports/own_general.json',
            'report_type' => 'General',
            'scope_id' => null,
            'user_id' => $this->secretary->id,
        ]);

        $ownAreaReport = SupplyReport::create([
            'category' => 'medsupply',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'file_path' => 'reports/own_area.json',
            'report_type' => 'Area',
            'scope_id' => $this->area->id,
            'user_id' => $this->secretary->id,
        ]);

        $otherAreaReport = EquipmentReport::create([
            'category' => 'medical',
            'date_of_accountability' => '2026-09-14',
            'year_of_report' => 2026,
            'file_path' => 'reports/other_area.json',
            'report_type' => 'Area',
            'scope_id' => $this->otherArea->id,
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->secretary)
            ->delete(route('reports.destroy', ['type' => 'equipment', 'id' => $ownGeneralReport->id]))
            ->assertSessionHas('error', 'Unauthorized to delete this report.');

        $this->actingAs($this->secretary)
            ->delete(route('reports.destroy', ['type' => 'supply', 'id' => $ownAreaReport->id]))
            ->assertSessionHas('error', 'Unauthorized to delete this report.');

        $this->actingAs($this->secretary)
            ->delete(route('reports.destroy', ['type' => 'equipment', 'id' => $otherAreaReport->id]))
            ->assertSessionHas('error', 'Unauthorized to delete this report.');

        $this->actingAs($this->secretary)
            ->post(route('reports.bulk_delete'), [
                'reports' => [
                    ['id' => $ownGeneralReport->id, 'type' => 'equipment'],
                    ['id' => $ownAreaReport->id, 'type' => 'supply'],
                    ['id' => $otherAreaReport->id, 'type' => 'equipment'],
                ],
            ]);

        $this->assertNotSoftDeleted('equipment_reports', ['id' => $ownGeneralReport->id]);
        $this->assertNotSoftDeleted('supply_reports', ['id' => $ownAreaReport->id]);
        $this->assertNotSoftDeleted('equipment_reports', ['id' => $otherAreaReport->id]);
    }

    public function test_secretary_can_view_all_divisions_and_areas(): void
    {
        $this->withoutVite();

        $divisionsResponse = $this->actingAs($this->secretary)->get(route('divisions.index'));
        $divisionsResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Divisions/Index')
            ->has('divisions.data', 2)
        );

        $areasResponse = $this->actingAs($this->secretary)->get(route('areas.index', ['my_division_only' => '0']));
        $areasResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Areas/Index')
            ->has('areas.data', 3)
        );
    }

    public function test_secretary_cannot_create_edit_or_delete_equipment_supplies_divisions_or_areas(): void
    {
        $equipment = Equipment::create([
            'category' => 'medical',
            'article' => 'Monitor',
            'description' => 'Patient monitor',
            'unit_value' => 1000,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $supply = Supply::create([
            'category' => 'medsupply',
            'article' => 'Gauze',
            'description' => 'Sterile gauze',
            'unit_value' => 50,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        // Equipment mutations
        $this->actingAs($this->secretary)->post(route('equipment.store'), [])->assertForbidden();
        $this->actingAs($this->secretary)->put(route('equipment.update', $equipment->id), [])->assertForbidden();
        $this->actingAs($this->secretary)->delete(route('equipment.destroy', $equipment->id), ['remarks' => 'Test'])->assertForbidden();

        // Supply mutations
        $this->actingAs($this->secretary)->post(route('supplies.store'), [])->assertForbidden();
        $this->actingAs($this->secretary)->put(route('supplies.update', $supply->id), [])->assertForbidden();
        $this->actingAs($this->secretary)->delete(route('supplies.destroy', $supply->id), ['remarks' => 'Test'])->assertForbidden();

        // Division mutations
        $this->actingAs($this->secretary)->post(route('divisions.store'), [])->assertForbidden();
        $this->actingAs($this->secretary)->put(route('divisions.update', $this->division->id), [])->assertForbidden();
        $this->actingAs($this->secretary)->delete(route('divisions.destroy', $this->division->id))->assertForbidden();

        // Area mutations
        $this->actingAs($this->secretary)->post(route('areas.store'), [])->assertForbidden();
        $this->actingAs($this->secretary)->put(route('areas.update', $this->area->id), [])->assertForbidden();
        $this->actingAs($this->secretary)->delete(route('areas.destroy', $this->area->id))->assertForbidden();
    }
}
