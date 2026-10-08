<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Division;
use App\Models\Area;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\DTOs\EquipmentDTO;
use App\Domain\Equipment\Actions\ImportEquipmentAction;
use App\Domain\Equipment\Actions\CreateEquipmentAction;
use App\Domain\Equipment\Actions\UpdateEquipmentAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EquipmentImportMatchingTest extends TestCase
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

        $this->division = Division::create(['div_code' => 'GSD', 'div_name' => 'General Services Division']);
        $this->area = Area::create([
            'area_name' => 'IT Department',
            'area_code' => 'IT',
            'division_id' => $this->division->id,
        ]);

        $this->adminUser = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $this->adminUser->assignRole('Admin');
    }

    public function test_equipment_dto_normalizes_zero_and_placeholders_to_null(): void
    {
        $dto1 = EquipmentDTO::fromArray([
            'serial_number' => '0',
            'property_number' => '0',
            'unit_value' => 100,
        ]);
        $this->assertNull($dto1->serial_number);
        $this->assertNull($dto1->property_number);

        $dto2 = EquipmentDTO::fromArray([
            'serial_number' => 'N/A',
            'property_number' => 'none',
            'unit_value' => 100,
        ]);
        $this->assertNull($dto2->serial_number);
        $this->assertNull($dto2->property_number);

        $dto3 = EquipmentDTO::fromArray([
            'serial_number' => 'SN-12345',
            'property_number' => 'PROP-98765',
            'unit_value' => 100,
        ]);
        $this->assertEquals('SN-12345', $dto3->serial_number);
        $this->assertEquals('PROP-98765', $dto3->property_number);
    }

    public function test_import_updates_when_serial_number_matches(): void
    {
        $action = new ImportEquipmentAction(new CreateEquipmentAction(), new UpdateEquipmentAction());

        $dto1 = EquipmentDTO::fromArray([
            'category' => 'ictequip',
            'article' => 'Laptop',
            'description' => 'Dell Latitude 5420',
            'serial_number' => 'SN-DELL-001',
            'property_number' => 'PROP-001',
            'unit_value' => 45000.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result1 = $action->execute($dto1);
        $this->assertEquals('created', $result1['action']);
        $this->assertCount(1, Equipment::all());

        // Re-import matching serial number with updated unit value
        $dto2 = EquipmentDTO::fromArray([
            'category' => 'ictequip',
            'article' => 'Laptop Updated',
            'description' => 'Dell Latitude 5420 Core i7',
            'serial_number' => 'SN-DELL-001',
            'property_number' => null,
            'unit_value' => 50000.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('updated', $result2['action']);
        $this->assertCount(1, Equipment::all());
        $this->assertEquals($result1['record']->id, $result2['record']->id);
        $this->assertEquals(50000.00, $result2['record']->unit_value);
    }

    public function test_import_updates_when_property_number_matches(): void
    {
        $action = new ImportEquipmentAction(new CreateEquipmentAction(), new UpdateEquipmentAction());

        $dto1 = EquipmentDTO::fromArray([
            'category' => 'fandf',
            'article' => 'Office Chair',
            'description' => 'Ergonomic Mesh Chair',
            'serial_number' => null,
            'property_number' => 'PROP-CHAIR-001',
            'unit_value' => 5500.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result1 = $action->execute($dto1);
        $this->assertEquals('created', $result1['action']);

        // Re-import matching property number
        $dto2 = EquipmentDTO::fromArray([
            'category' => 'fandf',
            'article' => 'Office Chair',
            'description' => 'Ergonomic Mesh Chair Blue',
            'serial_number' => '0', // should be treated as null
            'property_number' => 'PROP-CHAIR-001',
            'unit_value' => 6000.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('updated', $result2['action']);
        $this->assertCount(1, Equipment::all());
        $this->assertEquals($result1['record']->id, $result2['record']->id);
        $this->assertEquals(6000.00, $result2['record']->unit_value);
    }

    public function test_import_fallback_updates_when_no_numbers_and_category_article_description_match(): void
    {
        $action = new ImportEquipmentAction(new CreateEquipmentAction(), new UpdateEquipmentAction());

        $dto1 = EquipmentDTO::fromArray([
            'category' => 'fandf',
            'article' => 'Steel Cabinet',
            'description' => '4-Drawer Filing Cabinet Beige',
            'serial_number' => '0',
            'property_number' => '0',
            'unit_value' => 12000.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result1 = $action->execute($dto1);
        $this->assertEquals('created', $result1['action']);
        $this->assertNull($result1['record']->serial_number);
        $this->assertNull($result1['record']->property_number);
        $this->assertCount(1, Equipment::all());

        // Re-import matching category, article (case/trim difference), and description with no identifiers
        $dto2 = EquipmentDTO::fromArray([
            'category' => 'fandf',
            'article' => '  steel cabinet  ',
            'description' => '4-drawer filing cabinet beige',
            'serial_number' => '',
            'property_number' => null,
            'unit_value' => 13500.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('updated', $result2['action']);
        $this->assertCount(1, Equipment::all());
        $this->assertEquals($result1['record']->id, $result2['record']->id);
        $this->assertEquals(13500.00, $result2['record']->unit_value);
    }

    public function test_import_with_new_serial_number_does_not_fallback_to_matching_description(): void
    {
        $action = new ImportEquipmentAction(new CreateEquipmentAction(), new UpdateEquipmentAction());

        // Existing unnumbered item
        $dto1 = EquipmentDTO::fromArray([
            'category' => 'ictequip',
            'article' => 'Monitor',
            'description' => '24-inch IPS Monitor',
            'serial_number' => null,
            'property_number' => null,
            'unit_value' => 8000.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $action->execute($dto1);

        // New item that shares the same description, but has an explicit new serial number
        $dto2 = EquipmentDTO::fromArray([
            'category' => 'ictequip',
            'article' => 'Monitor',
            'description' => '24-inch IPS Monitor',
            'serial_number' => 'SN-BRAND-NEW-999',
            'property_number' => null,
            'unit_value' => 8500.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('created', $result2['action']);
        $this->assertCount(2, Equipment::all());
    }

    public function test_import_fallback_throws_domain_exception_when_multiple_records_match(): void
    {
        $action = new ImportEquipmentAction(new CreateEquipmentAction(), new UpdateEquipmentAction());

        // Create 2 items with identical category, article, and description in the same area
        Equipment::create([
            'category' => 'fandf',
            'article' => 'Conference Chair',
            'description' => 'Black Leather',
            'unit_value' => 3000.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        Equipment::create([
            'category' => 'fandf',
            'article' => 'Conference Chair',
            'description' => 'Black Leather',
            'unit_value' => 3000.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Multiple existing equipment records match Category 'fandf', Article 'Conference Chair', and Description in this area");

        // Attempt import without serial/property number
        $dto = EquipmentDTO::fromArray([
            'category' => 'fandf',
            'article' => 'Conference Chair',
            'description' => 'Black Leather',
            'serial_number' => '0',
            'property_number' => '0',
            'unit_value' => 3500.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $action->execute($dto);
    }

    public function test_http_equipment_import_controller_aborts_with_validation_error_on_duplicates(): void
    {
        // Create 2 items with identical description
        Equipment::create([
            'category' => 'fandf',
            'article' => 'Folding Table',
            'description' => 'Plastic 6ft White',
            'unit_value' => 2500.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        Equipment::create([
            'category' => 'fandf',
            'article' => 'Folding Table',
            'description' => 'Plastic 6ft White',
            'unit_value' => 2500.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $csvContent = implode("\n", [
            'category,article,description,unit_value,quantity_per_property_card,quantity_per_physical_count,division_id,area_id,serial_number,property_number',
            "fandf,Folding Table,Plastic 6ft White,2600.00,1,1,{$this->division->id},{$this->area->id},0,0",
        ]);

        $file = UploadedFile::fake()->createWithContent('equipment.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $error = session('errors')->first('file');
        $this->assertStringContainsString('Line 2', $error);
        $this->assertStringContainsString('Multiple existing equipment records match', $error);
    }

    public function test_http_normal_import_creates_records_successfully(): void
    {
        $csvContent = implode("\n", [
            'category,article,description,unit_value,quantity_per_property_card,quantity_per_physical_count,division_id,area_id,serial_number,property_number',
            "ictequip,Monitor,24-inch LED Display,7500.00,1,1,{$this->division->id},{$this->area->id},SN-MON-101,PROP-MON-101",
            "fandf,Desk Table,Wooden Study Table,4500.00,2,2,{$this->division->id},{$this->area->id},0,0",
        ]);

        $file = UploadedFile::fake()->createWithContent('equipment.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('equipment.index'));
        $response->assertSessionHas('success', 'Successfully imported: 2 new records created.');

        $this->assertCount(2, Equipment::all());

        $item1 = Equipment::where('serial_number', 'SN-MON-101')->first();
        $this->assertNotNull($item1);
        $this->assertEquals('Monitor', $item1->article);

        $item2 = Equipment::where('article', 'Desk Table')->first();
        $this->assertNotNull($item2);
        $this->assertNull($item2->serial_number);
        $this->assertNull($item2->property_number);
    }

    public function test_http_import_same_article_but_different_description_creates_new_separate_record(): void
    {
        // 1. Initial item exists
        Equipment::create([
            'category' => 'fandf',
            'article' => 'Office Chair',
            'description' => 'Ergonomic Mesh Black',
            'unit_value' => 5000.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
            'serial_number' => null,
            'property_number' => null,
        ]);

        $this->assertCount(1, Equipment::all());

        // 2. Import CSV with SAME article ("Office Chair") but DIFFERENT description ("Executive High-Back Leather")
        $csvContent = implode("\n", [
            'category,article,description,unit_value,quantity_per_property_card,quantity_per_physical_count,division_id,area_id,serial_number,property_number',
            "fandf,Office Chair,Executive High-Back Leather,9500.00,1,1,{$this->division->id},{$this->area->id},0,0",
        ]);

        $file = UploadedFile::fake()->createWithContent('equipment.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('equipment.index'));
        $response->assertSessionHas('success', 'Successfully imported: 1 new records created.');

        // Verify that 2 separate records now exist in the database
        $this->assertCount(2, Equipment::all());

        $chair1 = Equipment::where('description', 'Ergonomic Mesh Black')->first();
        $chair2 = Equipment::where('description', 'Executive High-Back Leather')->first();

        $this->assertNotNull($chair1);
        $this->assertNotNull($chair2);
        $this->assertNotEquals($chair1->id, $chair2->id);
    }

    public function test_http_import_same_category_article_and_description_updates_existing_record(): void
    {
        // 1. Initial item exists with unit_value 5000
        $existing = Equipment::create([
            'category' => 'fandf',
            'article' => 'Office Chair',
            'description' => 'Ergonomic Mesh Black',
            'unit_value' => 5000.00,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
            'serial_number' => null,
            'property_number' => null,
        ]);

        $this->assertCount(1, Equipment::all());

        // 2. Import CSV with exact SAME Category, Article, and Description, but updated unit_value (5800) and serial_number as 0
        $csvContent = implode("\n", [
            'category,article,description,unit_value,quantity_per_property_card,quantity_per_physical_count,division_id,area_id,serial_number,property_number',
            "fandf,Office Chair,Ergonomic Mesh Black,5800.00,1,1,{$this->division->id},{$this->area->id},0,0",
        ]);

        $file = UploadedFile::fake()->createWithContent('equipment.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('equipment.index'));
        $response->assertSessionHas('success', 'Successfully imported: 1 existing records updated.');

        // Verify that still only 1 record exists and it was updated
        $this->assertCount(1, Equipment::all());
        $fresh = $existing->fresh();
        $this->assertEquals(5800.00, $fresh->unit_value);
        $this->assertNull($fresh->serial_number);
        $this->assertNull($fresh->property_number);
    }

    public function test_http_import_error_handling_invalid_unit_value(): void
    {
        $csvContent = implode("\n", [
            'category,article,description,unit_value,quantity_per_property_card,quantity_per_physical_count,division_id,area_id,serial_number,property_number',
            "fandf,Office Chair,Ergonomic Mesh Black,0,1,1,{$this->division->id},{$this->area->id},0,0",
        ]);

        $file = UploadedFile::fake()->createWithContent('equipment.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $error = session('errors')->first('file');
        $this->assertStringContainsString('Line 2', $error);
        $this->assertStringContainsString('unit value', strtolower($error));
    }

    public function test_http_import_error_handling_missing_required_fields(): void
    {
        $csvContent = implode("\n", [
            'category,article,description,unit_value,quantity_per_property_card,quantity_per_physical_count,division_id,area_id,serial_number,property_number',
            ",,Missing Article and Category,5000.00,1,1,{$this->division->id},{$this->area->id},0,0",
        ]);

        $file = UploadedFile::fake()->createWithContent('equipment.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $error = session('errors')->first('file');
        $this->assertStringContainsString('Line 2', $error);
    }

    public function test_http_import_error_handling_unauthorized_division_for_non_superadmin(): void
    {
        $otherDivision = Division::create(['div_code' => 'ACC', 'div_name' => 'Accounting Division']);
        $otherArea = Area::create([
            'area_name' => 'Billing Office',
            'area_code' => 'BO',
            'division_id' => $otherDivision->id,
        ]);

        $encoderUser = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $encoderRole = Role::firstOrCreate(['name' => 'Encoder']);
        $encoderRole->givePermissionTo(['view_equipment', 'create_equipment']);
        $encoderUser->assignRole('Encoder');

        // Encoder attempts to upload to otherDivision and otherArea
        $csvContent = implode("\n", [
            'category,article,description,unit_value,quantity_per_property_card,quantity_per_physical_count,division_id,area_id,serial_number,property_number',
            "fandf,Office Chair,Ergonomic Mesh Black,5000.00,1,1,{$otherDivision->id},{$otherArea->id},0,0",
        ]);

        $file = UploadedFile::fake()->createWithContent('equipment.csv', $csvContent);

        $response = $this->actingAs($encoderUser)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $error = session('errors')->first('file');
        $this->assertStringContainsString('Line 2', $error);
        $this->assertStringContainsString('only allowed to upload data for your assigned division', $error);
    }
}
