<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Division;
use App\Models\Area;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Supplies\Models\Supply;
use App\Domain\Shared\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class DeleteRemarksTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $division;
    protected $area;
    protected $equipmentCategory;
    protected $supplyCategory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RolesAndPermissionsSeeder::class);
        
        $this->division = Division::create(['div_name' => 'Main Division', 'div_code' => 'DIV-01']);
        $this->area = Area::create(['area_name' => 'Main Area', 'division_id' => $this->division->id]);

        $this->user = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $this->user->assignRole('Superadmin');

        $this->equipmentCategory = Category::create([
            'code' => 'it_equip',
            'type' => 'equipment',
            'name' => 'IT Equipment'
        ]);

        $this->supplyCategory = Category::create([
            'code' => 'office_supplies',
            'type' => 'supply',
            'name' => 'Office Supplies'
        ]);
    }

    public function test_equipment_deletion_requires_remarks()
    {
        $equipment = Equipment::create([
            'article' => 'Laptop Dell XPS',
            'category' => $this->equipmentCategory->id,
            'description' => 'Developer laptop',
            'date_acquired' => '2025-01-01',
            'property_number' => 'PROP-101',
            'unit_of_measure' => 'unit',
            'unit_value' => 50000,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'total_value' => 50000,
            'status' => 'Serviceable',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('equipment.destroy', $equipment->id), [
            'remarks' => ''
        ]);

        $response->assertSessionHasErrors('remarks');
        $this->assertNull(Equipment::withTrashed()->find($equipment->id)->deleted_at);
    }

    public function test_equipment_single_deletion_stores_remarks_and_restoring_clears_it()
    {
        $equipment = Equipment::create([
            'article' => 'Laptop Dell XPS',
            'category' => $this->equipmentCategory->id,
            'description' => 'Developer laptop',
            'date_acquired' => '2025-01-01',
            'property_number' => 'PROP-102',
            'unit_of_measure' => 'unit',
            'unit_value' => 50000,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'total_value' => 50000,
            'status' => 'Unserviceable',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('equipment.destroy', $equipment->id), [
            'remarks' => 'Damaged motherboard beyond repair'
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSoftDeleted('equipment', ['id' => $equipment->id]);

        $deletedEquipment = Equipment::withTrashed()->find($equipment->id);
        $this->assertEquals('Damaged motherboard beyond repair', $deletedEquipment->delete_remarks);

        // Verify Recycle Bin endpoint returns delete_remarks
        $recycleBinResponse = $this->actingAs($this->user)->get(route('recycle-bin.index', ['tab' => 'equipment']));
        $recycleBinResponse->assertOk();

        // Restore equipment and verify delete_remarks is cleared
        $restoreResponse = $this->actingAs($this->user)->post(route('recycle-bin.restore'), [
            'items' => [
                ['id' => $equipment->id, 'type' => 'equipment']
            ]
        ]);

        $restoreResponse->assertSessionHasNoErrors();
        $restoredEquipment = Equipment::find($equipment->id);
        $this->assertNotNull($restoredEquipment);
        $this->assertNull($restoredEquipment->deleted_at);
        $this->assertNull($restoredEquipment->delete_remarks);
    }

    public function test_equipment_bulk_deletion_stores_remarks_for_all_items()
    {
        $equip1 = Equipment::create([
            'article' => 'Monitor 1',
            'category' => $this->equipmentCategory->id,
            'date_acquired' => '2025-01-01',
            'property_number' => 'PROP-201',
            'unit_of_measure' => 'unit',
            'unit_value' => 10000,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'total_value' => 10000,
            'status' => 'Unserviceable',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $equip2 = Equipment::create([
            'article' => 'Monitor 2',
            'category' => $this->equipmentCategory->id,
            'date_acquired' => '2025-01-01',
            'property_number' => 'PROP-202',
            'unit_of_measure' => 'unit',
            'unit_value' => 10000,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'total_value' => 10000,
            'status' => 'Unserviceable',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('equipment.bulk_delete'), [
            'ids' => [$equip1->id, $equip2->id],
            'remarks' => 'Bulk disposal of obsolete monitors'
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSoftDeleted('equipment', ['id' => $equip1->id]);
        $this->assertSoftDeleted('equipment', ['id' => $equip2->id]);

        $this->assertEquals('Bulk disposal of obsolete monitors', Equipment::withTrashed()->find($equip1->id)->delete_remarks);
        $this->assertEquals('Bulk disposal of obsolete monitors', Equipment::withTrashed()->find($equip2->id)->delete_remarks);
    }

    public function test_supply_single_deletion_stores_remarks_and_restoring_clears_it()
    {
        $supply = Supply::create([
            'article' => 'Ballpen Black',
            'category' => $this->supplyCategory->id,
            'stock_number' => 'STK-001',
            'unit_of_measure' => 'box',
            'unit_value' => 150,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'total_amount' => 1500,
            'status' => 'Available',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('supplies.destroy', $supply->id), [
            'remarks' => 'Expired ink batches'
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSoftDeleted('supplies', ['id' => $supply->id]);

        $deletedSupply = Supply::withTrashed()->find($supply->id);
        $this->assertEquals('Expired ink batches', $deletedSupply->delete_remarks);

        // Restore supply
        $restoreResponse = $this->actingAs($this->user)->post(route('recycle-bin.restore'), [
            'items' => [
                ['id' => $supply->id, 'type' => 'supplies']
            ]
        ]);

        $restoreResponse->assertSessionHasNoErrors();
        $restoredSupply = Supply::find($supply->id);
        $this->assertNotNull($restoredSupply);
        $this->assertNull($restoredSupply->deleted_at);
        $this->assertNull($restoredSupply->delete_remarks);
    }

    public function test_supply_bulk_deletion_stores_remarks_for_all_items()
    {
        $supply1 = Supply::create([
            'article' => 'Paper A4',
            'category' => $this->supplyCategory->id,
            'stock_number' => 'STK-002',
            'unit_of_measure' => 'ream',
            'unit_value' => 200,
            'balance_per_card' => 5,
            'on_hand_per_count' => 5,
            'total_amount' => 1000,
            'status' => 'Available',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $supply2 = Supply::create([
            'article' => 'Paper Legal',
            'category' => $this->supplyCategory->id,
            'stock_number' => 'STK-003',
            'unit_of_measure' => 'ream',
            'unit_value' => 250,
            'balance_per_card' => 5,
            'on_hand_per_count' => 5,
            'total_amount' => 1250,
            'status' => 'Available',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('supplies.bulk_delete'), [
            'ids' => [$supply1->id, $supply2->id],
            'remarks' => 'Damaged by water leak'
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSoftDeleted('supplies', ['id' => $supply1->id]);
        $this->assertSoftDeleted('supplies', ['id' => $supply2->id]);

        $this->assertEquals('Damaged by water leak', Supply::withTrashed()->find($supply1->id)->delete_remarks);
        $this->assertEquals('Damaged by water leak', Supply::withTrashed()->find($supply2->id)->delete_remarks);
    }
}
