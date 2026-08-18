<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Area;
use App\Models\Division;
use App\Domain\Equipment\Models\Equipment;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class EquipmentSerialToggleTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;
    protected Area $area;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_equipment', 'create_equipment', 'edit_equipment', 'delete_equipment',
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo($permissions);

        $this->division = Division::create(['div_code' => 'MED', 'div_name' => 'Medical Division']);
        $this->area = Area::create([
            'area_name' => 'ICU',
            'area_code' => 'ICU',
            'division_id' => $this->division->id,
        ]);

        $this->adminUser = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $this->adminUser->assignRole('Admin');
    }

    public function test_can_create_equipment_with_serial_number(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('equipment.store'), [
            'category' => 'ictequip',
            'article' => 'Desktop Computer',
            'description' => 'Dell OptiPlex 7090',
            'date_acquired' => '2026-01-15',
            'property_number' => 'PROP-2026-001',
            'serial_number' => 'SN-DELL-12345',
            'unit_of_measure' => 'unit',
            'unit_value' => 45000.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'status' => 'Serviceable',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response->assertRedirect(route('equipment.index'));
        $this->assertDatabaseHas('equipment', [
            'article' => 'Desktop Computer',
            'serial_number' => 'SN-DELL-12345',
            'property_number' => 'PROP-2026-001',
        ]);
    }

    public function test_can_create_equipment_without_serial_number(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('equipment.store'), [
            'category' => 'fandf',
            'article' => 'Office Table',
            'description' => 'Wooden Executive Desk',
            'date_acquired' => '2026-01-15',
            'property_number' => 'PROP-2026-002',
            'serial_number' => null,
            'unit_of_measure' => 'unit',
            'unit_value' => 8500.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'status' => 'Serviceable',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response->assertRedirect(route('equipment.index'));
        $this->assertDatabaseHas('equipment', [
            'article' => 'Office Table',
            'serial_number' => null,
            'property_number' => 'PROP-2026-002',
        ]);
    }

    public function test_can_update_equipment_and_remove_serial_number(): void
    {
        $equipment = Equipment::create([
            'category' => 'fandf',
            'article' => 'Office Chair',
            'description' => 'Ergonomic Chair',
            'serial_number' => 'SN-CHAIR-999',
            'unit_value' => 3500.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'status' => 'Serviceable',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('equipment.update', $equipment->id), [
            'category' => 'fandf',
            'article' => 'Office Chair',
            'description' => 'Ergonomic Chair Updated',
            'serial_number' => null,
            'unit_value' => 3500.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'status' => 'Serviceable',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response->assertRedirect(route('equipment.index'));
        $equipment->refresh();
        $this->assertNull($equipment->serial_number);
        $this->assertEquals('Ergonomic Chair Updated', $equipment->description);
    }

    public function test_csv_template_hints_show_serial_number_optional(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('equipment.template'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->streamedContent(), 'Serial Number (Optional)'));
    }

    public function test_csv_import_succeeds_without_serial_number(): void
    {
        $csvContent = implode("\n", [
            'category,article,description,date_acquired,property_number,serial_number,unit_of_measure,unit_value,quantity_per_property_card,quantity_per_physical_count,remarks,end_user,status,division_id,area_id',
            'Hint: Category Code,Name,Desc,YYYY-MM-DD,PropNo,SerialNo,UOM,Val,Card,Phys,Remarks,User,Status,Div,Area',
            "fandf,Wooden Chair,Standard Chair,2026-02-01,PROP-CHAIR-101,,unit,1500,2,2,None,Staff,Serviceable,{$this->division->id},{$this->area->id}"
        ]);

        $file = UploadedFile::fake()->createWithContent('equipment.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('equipment.index'));
        $this->assertDatabaseHas('equipment', [
            'article' => 'Wooden Chair',
            'serial_number' => null,
            'property_number' => 'PROP-CHAIR-101',
        ]);
    }

    public function test_csv_import_succeeds_with_serial_number(): void
    {
        $csvContent = implode("\n", [
            'category,article,description,date_acquired,property_number,serial_number,unit_of_measure,unit_value,quantity_per_property_card,quantity_per_physical_count,remarks,end_user,status,division_id,area_id',
            'Hint: Category Code,Name,Desc,YYYY-MM-DD,PropNo,SerialNo,UOM,Val,Card,Phys,Remarks,User,Status,Div,Area',
            "ictequip,Laptop,Lenovo ThinkPad,2026-02-01,PROP-LAP-101,SN-LAP-8888,unit,60000,1,1,None,IT,Serviceable,{$this->division->id},{$this->area->id}"
        ]);

        $file = UploadedFile::fake()->createWithContent('equipment.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('equipment.index'));
        $this->assertDatabaseHas('equipment', [
            'article' => 'Laptop',
            'serial_number' => 'SN-LAP-8888',
            'property_number' => 'PROP-LAP-101',
        ]);
    }
}
