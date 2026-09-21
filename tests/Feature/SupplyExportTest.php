<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Area;
use App\Models\Division;
use App\Domain\Supplies\Models\Supply;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplyExportTest extends TestCase
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
            'area_name' => 'Pharmacy',
            'area_code' => 'PHARM',
            'division_id' => $this->division1->id,
        ]);

        $this->area2 = Area::create([
            'area_name' => 'Supply Room',
            'area_code' => 'SR',
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

        // Create test supply items
        Supply::create([
            'category' => 'medsurg',
            'article' => 'Surgical Gloves',
            'description' => 'Latex Medium',
            'stock_number' => 'STK-001',
            'unit_value' => 250,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'status' => 'Available',
            'expiry_date' => '2027-12-31',
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);

        Supply::create([
            'category' => 'officesup',
            'article' => 'Bond Paper A4',
            'description' => '70gsm 500 sheets',
            'stock_number' => 'STK-002',
            'unit_value' => 220,
            'balance_per_card' => 50,
            'on_hand_per_count' => 50,
            'status' => 'Available',
            'division_id' => $this->division2->id,
            'area_id' => $this->area2->id,
        ]);
    }

    public function test_unauthorized_roles_cannot_export_supplies(): void
    {
        // Unauthenticated
        $this->get(route('supplies.export'))
            ->assertRedirect(route('login'));

        // Admin
        $this->actingAs($this->admin)
            ->get(route('supplies.export'))
            ->assertStatus(403);

        // Encoder
        $this->actingAs($this->encoder)
            ->get(route('supplies.export'))
            ->assertStatus(403);
    }

    public function test_superadmin_and_developer_can_export_supplies(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('supplies.export'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $devResponse = $this->actingAs($this->developer)->get(route('supplies.export'));
        $devResponse->assertStatus(200);
    }

    public function test_template_format_outputs_import_compatible_headers(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('supplies.export', ['format' => 'template']));
        $response->assertStatus(200);

        $content = $response->streamedContent();
        $this->assertStringContainsString('category,article,description,stock_number,expiry_date', $content);
        $this->assertStringContainsString('Surgical Gloves', $content);
        $this->assertStringContainsString('Bond Paper A4', $content);
    }

    public function test_full_format_outputs_audit_headers_and_names(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('supplies.export', ['format' => 'full']));
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
        $response = $this->actingAs($this->superadmin)->get(route('supplies.export', [
            'division_id' => $this->division1->id,
            'format' => 'template',
        ]));
        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('Surgical Gloves', $content);
        $this->assertStringNotContainsString('Bond Paper A4', $content);

        // Filter by Division 2 and Area 2
        $response2 = $this->actingAs($this->superadmin)->get(route('supplies.export', [
            'division_id' => $this->division2->id,
            'area_id' => $this->area2->id,
            'format' => 'template',
        ]));
        $response2->assertStatus(200);
        $content2 = $response2->streamedContent();
        $this->assertStringContainsString('Bond Paper A4', $content2);
        $this->assertStringNotContainsString('Surgical Gloves', $content2);
    }
}
