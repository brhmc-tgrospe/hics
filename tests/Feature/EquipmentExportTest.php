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

class EquipmentExportTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division1;
    protected Division $division2;
    protected Area $area1;
    protected Area $area2;
    protected User $superadmin;
    protected User $developer;
    protected User $admin;
    protected User $encoder;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = ['Superadmin', 'Developer', 'Admin', 'Encoder'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $this->division1 = Division::create(['div_code' => 'MED', 'div_name' => 'Medical Division']);
        $this->division2 = Division::create(['div_code' => 'ADM', 'div_name' => 'Administrative Division']);

        $this->area1 = Area::create([
            'area_name' => 'ICU',
            'area_code' => 'ICU',
            'division_id' => $this->division1->id,
        ]);

        $this->area2 = Area::create([
            'area_name' => 'HR Office',
            'area_code' => 'HR',
            'division_id' => $this->division2->id,
        ]);

        $this->superadmin = User::factory()->create();
        $this->superadmin->assignRole('Superadmin');

        $this->developer = User::factory()->create();
        $this->developer->assignRole('Developer');

        $this->admin = User::factory()->create([
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->encoder = User::factory()->create([
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);
        $this->encoder->assignRole('Encoder');

        // Create test equipment items
        Equipment::create([
            'category' => 'ictequip',
            'article' => 'Desktop PC 1',
            'description' => 'Dell PC',
            'property_number' => 'PROP-001',
            'unit_value' => 40000,
            'quantity_per_property_card' => 1,
            'quantity_per_physical_count' => 1,
            'status' => 'Serviceable',
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);

        Equipment::create([
            'category' => 'fandf',
            'article' => 'Office Table 2',
            'description' => 'Wooden Table',
            'property_number' => 'PROP-002',
            'unit_value' => 8000,
            'quantity_per_property_card' => 2,
            'quantity_per_physical_count' => 2,
            'status' => 'Serviceable',
            'division_id' => $this->division2->id,
            'area_id' => $this->area2->id,
        ]);
    }

    public function test_unauthorized_roles_cannot_export_equipment(): void
    {
        // Unauthenticated
        $this->get(route('equipment.export'))
            ->assertRedirect(route('login'));

        // Admin
        $this->actingAs($this->admin)
            ->get(route('equipment.export'))
            ->assertStatus(403);

        // Encoder
        $this->actingAs($this->encoder)
            ->get(route('equipment.export'))
            ->assertStatus(403);
    }

    public function test_superadmin_and_developer_can_export_equipment(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('equipment.export'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $devResponse = $this->actingAs($this->developer)->get(route('equipment.export'));
        $devResponse->assertStatus(200);
    }

    public function test_template_format_outputs_import_compatible_headers(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('equipment.export', ['format' => 'template']));
        $response->assertStatus(200);

        $content = $response->streamedContent();
        $this->assertStringContainsString('category,article,description,date_acquired,property_number,serial_number', $content);
        $this->assertStringContainsString('Desktop PC 1', $content);
        $this->assertStringContainsString('Office Table 2', $content);
    }

    public function test_full_format_outputs_audit_headers_and_names(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('equipment.export', ['format' => 'full']));
        $response->assertStatus(200);

        $content = $response->streamedContent();
        $this->assertStringContainsString('Category Name', $content);
        $this->assertStringContainsString('Article', $content);
        $this->assertStringContainsString('Description', $content);
        $this->assertStringContainsString('Division Name', $content);
        $this->assertStringContainsString('Area Name', $content);
        $this->assertStringContainsString('Medical Division', $content);
        $this->assertStringContainsString('Administrative Division', $content);
    }

    public function test_filtering_by_division_and_area(): void
    {
        // Filter by Division 1 only
        $response = $this->actingAs($this->superadmin)->get(route('equipment.export', [
            'division_id' => $this->division1->id,
            'format' => 'template',
        ]));
        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('Desktop PC 1', $content);
        $this->assertStringNotContainsString('Office Table 2', $content);

        // Filter by Division 2 and Area 2
        $response2 = $this->actingAs($this->superadmin)->get(route('equipment.export', [
            'division_id' => $this->division2->id,
            'area_id' => $this->area2->id,
            'format' => 'template',
        ]));
        $response2->assertStatus(200);
        $content2 = $response2->streamedContent();
        $this->assertStringContainsString('Office Table 2', $content2);
        $this->assertStringNotContainsString('Desktop PC 1', $content2);
    }
}
