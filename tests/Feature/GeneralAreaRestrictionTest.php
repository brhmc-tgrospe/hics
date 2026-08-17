<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Area;
use App\Models\Division;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Supplies\Models\Supply;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class GeneralAreaRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;
    protected Area $generalArea;
    protected Area $regularArea;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view_equipment', 'create_equipment', 'edit_equipment', 'delete_equipment',
            'view_supplies', 'create_supplies', 'edit_supplies', 'delete_supplies',
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'view_divisions', 'view_areas'
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo($permissions);

        $encoderRole = Role::firstOrCreate(['name' => 'Encoder']);
        $encoderRole->givePermissionTo([
            'view_equipment', 'create_equipment', 'edit_equipment', 'delete_equipment',
            'view_supplies', 'create_supplies', 'edit_supplies', 'delete_supplies',
            'view_divisions', 'view_areas'
        ]);

        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin']);
        $superadminRole->givePermissionTo($permissions);

        $devRole = Role::firstOrCreate(['name' => 'Developer']);

        // Create Division & Areas
        $this->division = Division::create(['div_code' => 'MED', 'div_name' => 'Medical Division']);
        $this->generalArea = Area::create([
            'area_name' => 'General Area',
            'division_id' => $this->division->id,
        ]);
        $this->regularArea = Area::create([
            'area_name' => 'Emergency Room',
            'division_id' => $this->division->id,
        ]);
    }

    public function test_user_is_in_general_area_method()
    {
        $generalUser = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);

        $regularUser = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->regularArea->id,
        ]);

        $this->assertTrue($generalUser->isInGeneralArea());
        $this->assertFalse($regularUser->isInGeneralArea());
    }

    public function test_general_area_user_cannot_create_equipment()
    {
        $encoder = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);
        $encoder->assignRole('Encoder');

        $response = $this->actingAs($encoder)->post(route('equipment.store'), [
            'article' => 'Defibrillator',
            'description' => 'Medical equipment',
            'serial_number' => 'SN-12345',
            'unit_value' => 50000,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);

        $response->assertForbidden();
    }

    public function test_general_area_user_cannot_create_supplies()
    {
        $encoder = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);
        $encoder->assignRole('Encoder');

        $response = $this->actingAs($encoder)->post(route('supplies.store'), [
            'category' => 'DRMEDS',
            'description' => 'Paracetamol 500mg',
            'unit_value' => 5.50,
            'balance_per_card' => 100,
            'on_hand_per_count' => 100,
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);

        $response->assertForbidden();
    }

    public function test_general_area_user_cannot_import_equipment()
    {
        $encoder = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);
        $encoder->assignRole('Encoder');

        $csvContent = "category,article,description,date_acquired,property_number,serial_number,unit_of_measure,unit_value,quantity_per_property_card,quantity_per_physical_count,remarks,end_user,status,division_id,area_id\n";
        $csvContent .= "ictequip,Laptop,Dell Inspiron,2023-01-01,PN-1,SN-999,unit,45000,1,1,None,John,Serviceable,{$this->division->id},{$this->generalArea->id}\n";

        $file = UploadedFile::fake()->createWithContent('equipment.csv', $csvContent);

        $response = $this->actingAs($encoder)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        // Either forbidden or validation error with custom message
        $this->assertTrue(
            $response->status() === 403 || $response->isInvalid()
        );
    }

    public function test_admin_cannot_assign_user_to_general_area_on_create()
    {
        $admin = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->regularArea->id,
        ]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'username' => 'janedoe',
            'email' => 'janedoe@example.com',
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
            'role' => 'Encoder',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('area_id');
        $this->assertDatabaseMissing('users', ['username' => 'janedoe']);
    }

    public function test_admin_cannot_assign_user_to_general_area_on_update()
    {
        $admin = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->regularArea->id,
        ]);
        $admin->assignRole('Admin');

        $targetUser = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->regularArea->id,
        ]);
        $targetUser->assignRole('Encoder');

        $response = $this->actingAs($admin)->put(route('users.update', $targetUser->id), [
            'first_name' => $targetUser->first_name,
            'last_name' => $targetUser->last_name,
            'username' => $targetUser->username,
            'email' => $targetUser->email,
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
            'role' => 'Encoder',
        ]);

        $response->assertSessionHasErrors('area_id');
        $this->assertEquals($this->regularArea->id, $targetUser->fresh()->area_id);
    }

    public function test_admin_in_general_area_can_create_equipment_in_valid_area()
    {
        $admin = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('equipment.store'), [
            'article' => 'Ultrasound Machine',
            'description' => 'Medical device',
            'serial_number' => 'SN-ADMIN-001',
            'unit_value' => 120000,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->regularArea->id,
        ]);

        $response->assertRedirect(route('equipment.index'));
        $this->assertDatabaseHas('equipment', [
            'serial_number' => 'SN-ADMIN-001',
            'area_id' => $this->regularArea->id,
        ]);
    }

    public function test_admin_in_general_area_can_create_supplies_in_valid_area()
    {
        $admin = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('supplies.store'), [
            'category' => 'DRMEDS',
            'article' => 'Amoxicillin 500mg',
            'description' => 'Antibiotic capsules',
            'expiry_date' => '2026-12-31',
            'unit_value' => 8.00,
            'balance_per_card' => 50,
            'on_hand_per_count' => 50,
            'division_id' => $this->division->id,
            'area_id' => $this->regularArea->id,
        ]);

        $response->assertRedirect(route('supplies.index'));
        $this->assertDatabaseHas('supplies', [
            'article' => 'Amoxicillin 500mg',
            'area_id' => $this->regularArea->id,
        ]);
    }

    public function test_admin_cannot_create_equipment_assigned_to_general_area()
    {
        $admin = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('equipment.store'), [
            'article' => 'General Area Item',
            'description' => 'Testing disallowed area',
            'serial_number' => 'SN-GEN-001',
            'unit_value' => 1000,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);

        $response->assertSessionHasErrors('area_id');
        $this->assertDatabaseMissing('equipment', ['serial_number' => 'SN-GEN-001']);
    }

    public function test_admin_cannot_create_supplies_assigned_to_general_area()
    {
        $admin = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('supplies.store'), [
            'category' => 'DRMEDS',
            'article' => 'General Area Medicine',
            'description' => 'Testing disallowed area',
            'unit_value' => 5.00,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'division_id' => $this->division->id,
            'area_id' => $this->generalArea->id,
        ]);

        $response->assertSessionHasErrors('area_id');
        $this->assertDatabaseMissing('supplies', ['article' => 'General Area Medicine']);
    }
}
