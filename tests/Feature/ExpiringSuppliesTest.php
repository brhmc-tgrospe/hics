<?php

namespace Tests\Feature;

use App\Domain\Shared\Models\Category;
use App\Domain\Supplies\Actions\GetExpiringSuppliesAction;
use App\Domain\Supplies\Models\Supply;
use App\Domain\Supply\Models\SupplyReport;
use App\Models\Area;
use App\Models\Division;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpiringSuppliesTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division1;
    protected Division $division2;
    protected Area $area1;
    protected Area $area2;
    protected User $superadmin;
    protected User $secretary;
    protected User $admin;
    protected User $unassignedAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $viewSupplies = Permission::firstOrCreate(['name' => 'view_supplies']);
        $generateReports = Permission::firstOrCreate(['name' => 'generate_reports']);

        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin']);
        $superadminRole->givePermissionTo([$viewSupplies, $generateReports]);

        $secretaryRole = Role::firstOrCreate(['name' => 'Secretary']);
        $secretaryRole->givePermissionTo([$viewSupplies, $generateReports]);

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo([$viewSupplies, $generateReports]);

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

        $this->superadmin = User::factory()->create([
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);
        $this->superadmin->assignRole('Superadmin');

        $this->secretary = User::factory()->create([
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);
        $this->secretary->assignRole('Secretary');

        $this->admin = User::factory()->create([
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->unassignedAdmin = User::factory()->create([
            'division_id' => null,
            'area_id' => $this->area1->id,
        ]);
        $this->unassignedAdmin->assignRole('Admin');

        Category::updateOrCreate(
            ['code' => 'drmeds', 'type' => 'supply'],
            ['name' => 'Drugs and Medicines', 'has_expiration_date' => true]
        );

        Category::updateOrCreate(
            ['code' => 'mssup', 'type' => 'supply'],
            ['name' => 'Medical and Surgical Supplies', 'has_expiration_date' => true]
        );

        Category::updateOrCreate(
            ['code' => 'officesup', 'type' => 'supply'],
            ['name' => 'Office Supplies', 'has_expiration_date' => false]
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedSampleSupplies(): void
    {
        // Included: expiring in 3 months (drmeds)
        Supply::create([
            'category' => 'drmeds',
            'article' => 'Amoxicillin 500mg',
            'description' => 'Antibiotic capsules',
            'stock_number' => 'DR-001',
            'unit_of_measure' => 'box',
            'unit_value' => 50,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'status' => 'Available',
            'expiry_date' => '2027-02-15',
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);

        // Included: expiring on exact 6-month boundary (drmeds)
        Supply::create([
            'category' => 'drmeds',
            'article' => 'Paracetamol IV',
            'description' => '100ml vial',
            'stock_number' => 'DR-002',
            'unit_of_measure' => 'vial',
            'unit_value' => 100,
            'balance_per_card' => 5,
            'on_hand_per_count' => 5,
            'status' => 'Available',
            'expiry_date' => '2027-05-18',
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);

        // Included: already expired (mssup, division2)
        Supply::create([
            'category' => 'mssup',
            'article' => 'Sterile Sutures',
            'description' => 'Absorbable 3-0',
            'stock_number' => 'MS-001',
            'unit_of_measure' => 'pack',
            'unit_value' => 25,
            'balance_per_card' => 4,
            'on_hand_per_count' => 4,
            'status' => 'Available',
            'expiry_date' => '2026-10-01',
            'division_id' => $this->division2->id,
            'area_id' => $this->area2->id,
        ]);

        // Excluded: 6 months + 1 day
        Supply::create([
            'category' => 'drmeds',
            'article' => 'Ceftriaxone 1g',
            'description' => 'Antibiotic powder',
            'stock_number' => 'DR-003',
            'unit_of_measure' => 'vial',
            'unit_value' => 200,
            'balance_per_card' => 20,
            'on_hand_per_count' => 20,
            'status' => 'Available',
            'expiry_date' => '2027-05-19',
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);

        // Excluded: null expiry_date
        Supply::create([
            'category' => 'officesup',
            'article' => 'Bond Paper A4',
            'description' => '70gsm',
            'stock_number' => 'OF-001',
            'unit_of_measure' => 'ream',
            'unit_value' => 220,
            'balance_per_card' => 50,
            'on_hand_per_count' => 50,
            'status' => 'Available',
            'expiry_date' => null,
            'division_id' => $this->division2->id,
            'area_id' => $this->area2->id,
        ]);
    }

    public function test_supplies_index_passes_grouped_expiring_supplies_within_6_months_utc8(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 10, 0, 0, '+08:00'));
        $this->seedSampleSupplies();

        $response = $this->actingAs($this->superadmin)->get(route('supplies.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Supplies/Index')
            ->where('expiringSupplies', function ($expiring) {
                $this->assertSame('2026-11-18', $expiring['current_date_utc8']);
                $this->assertSame('2027-05-18', $expiring['cutoff_date_utc8']);
                $this->assertSame(3, $expiring['total_items_count']);
                $this->assertSame(19, $expiring['total_quantity']);
                $this->assertEquals(1100.0, (float) $expiring['total_amount']);
                $this->assertCount(2, $expiring['categories']);

                $categoriesByCode = collect($expiring['categories'])->keyBy('category_code');

                $mssup = $categoriesByCode->get('mssup');
                $this->assertNotNull($mssup);
                $this->assertSame('Medical and Surgical Supplies', $mssup['category_name']);
                $this->assertSame(1, $mssup['items_count']);
                $this->assertSame(4, $mssup['total_quantity']);
                $this->assertEquals(100.0, (float) $mssup['total_amount']);
                $this->assertTrue($mssup['items'][0]['is_expired']);
                $this->assertSame('Sterile Sutures', $mssup['items'][0]['article']);

                $drmeds = $categoriesByCode->get('drmeds');
                $this->assertNotNull($drmeds);
                $this->assertSame('Drugs and Medicines', $drmeds['category_name']);
                $this->assertSame(2, $drmeds['items_count']);
                $this->assertSame(15, $drmeds['total_quantity']);
                $this->assertEquals(1000.0, (float) $drmeds['total_amount']);
                $this->assertFalse($drmeds['items'][0]['is_expired']);

                return true;
            })
        );
    }

    public function test_dashboard_passes_role_scoped_expiring_supplies(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 10, 0, 0, '+08:00'));
        $this->seedSampleSupplies();

        // Superadmin sees all 3 expiring supplies across both divisions
        $superResponse = $this->actingAs($this->superadmin)->get(route('dashboard'));
        $superResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->where('expiringSupplies.total_items_count', 3)
            ->where('expiringSupplies.total_quantity', 19)
            ->where('expiringSupplies', fn ($expiring) => (float) $expiring['total_amount'] === 1100.0)
        );

        // Division 1 Admin sees only Division 1 expiring supplies (2 drmeds items)
        $adminResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $adminResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->where('expiringSupplies', function ($expiring) {
                $this->assertSame(2, $expiring['total_items_count']);
                $this->assertSame(15, $expiring['total_quantity']);
                $this->assertEquals(1000.0, (float) $expiring['total_amount']);
                $this->assertCount(1, $expiring['categories']);
                $this->assertSame('drmeds', $expiring['categories'][0]['category_code']);
                return true;
            })
        );

        // Unassigned non-superadmin user gets empty expiring supplies
        $unassignedResponse = $this->actingAs($this->unassignedAdmin)->get(route('dashboard'));
        $unassignedResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->where('expiringSupplies.total_items_count', 0)
            ->where('expiringSupplies.categories', [])
        );
    }

    public function test_utc8_timezone_is_used_even_when_php_default_timezone_is_utc(): void
    {
        $originalTz = date_default_timezone_get();

        try {
            date_default_timezone_set('UTC');
            // 2026-11-18 17:00:00 UTC is 2026-11-19 01:00:00 +08:00 (UTC+8)
            Carbon::setTestNow(Carbon::create(2026, 11, 18, 17, 0, 0, 'UTC'));
            $this->seedSampleSupplies();

            $result = app(GetExpiringSuppliesAction::class)->execute($this->superadmin);

            // Under UTC+8, today is 2026-11-19 so 6-month cutoff is 2027-05-19 (includes Ceftriaxone 1g)
            $this->assertSame('2026-11-19', $result['current_date_utc8']);
            $this->assertSame('2027-05-19', $result['cutoff_date_utc8']);
            $this->assertSame(4, $result['total_items_count']);
            $this->assertSame(39, $result['total_quantity']);
            $this->assertEquals(5100.0, (float) $result['total_amount']);
        } finally {
            date_default_timezone_set($originalTz);
        }
    }

    public function test_empty_state_when_no_supplies_expire_within_6_months(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 10, 0, 0, '+08:00'));

        Supply::create([
            'category' => 'drmeds',
            'article' => 'Far Future Med',
            'description' => 'Valid far beyond 6 months',
            'unit_value' => 100,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'status' => 'Available',
            'expiry_date' => '2027-12-31',
            'division_id' => $this->division1->id,
            'area_id' => $this->area1->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('supplies.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('expiringSupplies.total_items_count', 0)
            ->where('expiringSupplies.total_quantity', 0)
            ->where('expiringSupplies.categories', [])
        );
    }

    public function test_supplies_index_filters_apply_to_expiring_supplies_container(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 10, 0, 0, '+08:00'));
        $this->seedSampleSupplies();

        $response = $this->actingAs($this->superadmin)->get(route('supplies.index', [
            'category' => 'mssup',
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('expiringSupplies', function ($expiring) {
                $this->assertSame(1, $expiring['total_items_count']);
                $this->assertSame(4, $expiring['total_quantity']);
                $this->assertEquals(100.0, (float) $expiring['total_amount']);
                $this->assertCount(1, $expiring['categories']);
                $this->assertSame('mssup', $expiring['categories'][0]['category_code']);
                return true;
            })
        );
    }

    public function test_preview_expiring_report_supports_all_and_specific_categories_with_user_attribution(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 10, 0, 0, '+08:00'));
        $this->seedSampleSupplies();

        $expectedUser = trim($this->superadmin->first_name . ' ' . $this->superadmin->last_name);

        // Preview all categories
        $allResponse = $this->actingAs($this->superadmin)->get(route('supplies.expiring_report.preview', [
            'categories' => ['All'],
        ]));

        $allResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Supplies/ExpiringReport')
            ->where('categoryName', 'ALL CATEGORIES')
            ->where('generatedBy', $expectedUser)
            ->where('savedReportId', null)
            ->where('expiringSupplies.total_items_count', 3)
            ->has('expiringSupplies.categories', 2)
        );

        // Preview specific category (drmeds)
        $filteredResponse = $this->actingAs($this->superadmin)->get(route('supplies.expiring_report.preview', [
            'categories' => ['drmeds'],
        ]));

        $filteredResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Supplies/ExpiringReport')
            ->where('categoryName', 'DRUGS AND MEDICINES')
            ->where('generatedBy', $expectedUser)
            ->where('expiringSupplies.total_items_count', 2)
            ->has('expiringSupplies.categories', 1)
            ->where('expiringSupplies.categories.0.category_code', 'drmeds')
        );

        // Preview expired items only
        $expiredOnlyResponse = $this->actingAs($this->superadmin)->get(route('supplies.expiring_report.preview', [
            'categories' => ['All'],
            'status_filter' => 'expired',
        ]));

        $expiredOnlyResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Supplies/ExpiringReport')
            ->where('statusFilter', 'expired')
            ->where('expiringSupplies.total_items_count', 1)
            ->where('expiringSupplies.categories.0.category_code', 'mssup')
        );
    }

    public function test_save_expiring_report_persists_to_reports_area_and_can_be_viewed(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 10, 0, 0, '+08:00'));
        $this->seedSampleSupplies();

        $expectedUser = trim($this->superadmin->first_name . ' ' . $this->superadmin->last_name);

        $saveResponse = $this->actingAs($this->superadmin)->postJson(route('supplies.expiring_report.save'), [
            'categories' => ['drmeds'],
            'status_filter' => 'all',
        ]);

        $saveResponse->assertOk()->assertJsonStructure(['id', 'message']);

        $report = SupplyReport::findOrFail($saveResponse->json('id'));
        $this->assertSame('drmeds', $report->category);
        $this->assertSame('Expiring Supplies', $report->report_period);
        $this->assertSame('General', $report->report_type);
        $this->assertSame($this->superadmin->id, $report->user_id);
        Storage::disk('local')->assertExists($report->file_path);

        // Viewing the saved report opens ExpiringReport with savedReportId set
        $showResponse = $this->actingAs($this->superadmin)->get(route('supplies.report.show', $report->id));
        $showResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Supplies/ExpiringReport')
            ->where('savedReportId', $report->id)
            ->where('generatedBy', $expectedUser)
            ->where('categoryName', 'DRUGS AND MEDICINES')
            ->where('expiringSupplies.total_items_count', 2)
        );

        // Listed in Reports Area
        $reportsIndexResponse = $this->actingAs($this->superadmin)->get(route('reports.index'));
        $reportsIndexResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->has('reports.data', 1)
            ->where('reports.data.0.name', 'Expiring Supplies Report - Drugs and Medicines - 2026')
            ->where('reports.data.0.created_by', $expectedUser)
        );
    }

    public function test_secretary_sees_hospital_wide_expiring_supplies_on_dashboard_and_reports(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 10, 0, 0, '+08:00'));
        $this->seedSampleSupplies();

        // Dashboard shows all 3 expiring supplies across both divisions
        $dashboardResponse = $this->actingAs($this->secretary)->get(route('dashboard'));
        $dashboardResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->where('expiringSupplies', function ($expiring) {
                $this->assertSame(3, $expiring['total_items_count']);
                $this->assertSame(19, $expiring['total_quantity']);
                $this->assertEquals(1100.0, (float) $expiring['total_amount']);
                $this->assertCount(2, $expiring['categories']);
                return true;
            })
        );

        // Supplies index shows all 3 expiring supplies across both divisions
        $suppliesResponse = $this->actingAs($this->secretary)->get(route('supplies.index'));
        $suppliesResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Supplies/Index')
            ->where('expiringSupplies.total_items_count', 3)
            ->where('expiringSupplies.total_quantity', 19)
            ->where('expiringSupplies', fn ($expiring) => (float) $expiring['total_amount'] === 1100.0)
        );

        // Expiring report preview shows all 3 expiring supplies across both divisions
        $reportResponse = $this->actingAs($this->secretary)->get(route('supplies.expiring_report.preview', [
            'categories' => ['All'],
        ]));
        $reportResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Supplies/ExpiringReport')
            ->where('expiringSupplies.total_items_count', 3)
            ->where('expiringSupplies.total_quantity', 19)
            ->where('expiringSupplies', fn ($expiring) => (float) $expiring['total_amount'] === 1100.0)
        );
    }

    public function test_expiring_report_supports_division_and_area_filters_and_sorts_alphabetically(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 10, 0, 0, '+08:00'));
        $this->seedSampleSupplies();

        // Add another drmeds item in division2/area2 whose article starts with 'B' and expires later than 'Amoxicillin'
        // plus an item starting with 'Aardvark' in division2/area2 expiring latest, to verify alphabetical sorting regardless of division/area/expiry
        Supply::create([
            'category' => 'drmeds',
            'article' => 'Betamethasone Cream',
            'description' => 'Topical ointment',
            'stock_number' => 'DR-010',
            'unit_of_measure' => 'tube',
            'unit_value' => 80,
            'balance_per_card' => 3,
            'on_hand_per_count' => 3,
            'status' => 'Available',
            'expiry_date' => '2027-04-01',
            'division_id' => $this->division2->id,
            'area_id' => $this->area2->id,
        ]);

        Supply::create([
            'category' => 'drmeds',
            'article' => 'Aardvark Saline',
            'description' => 'IV solution',
            'stock_number' => 'DR-011',
            'unit_of_measure' => 'bottle',
            'unit_value' => 60,
            'balance_per_card' => 2,
            'on_hand_per_count' => 2,
            'status' => 'Available',
            'expiry_date' => '2027-05-01',
            'division_id' => $this->division2->id,
            'area_id' => $this->area2->id,
        ]);

        // Dashboard provides divisions and areas props for filter dropdowns
        $this->actingAs($this->superadmin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('divisions', 2)
                ->has('areas', 2)
            );

        // Preview all divisions/areas: categories sorted alphabetically ('Drugs and Medicines', 'Medical and Surgical Supplies')
        // and items within drmeds sorted alphabetically regardless of division/area/expiry
        $allResponse = $this->actingAs($this->superadmin)->get(route('supplies.expiring_report.preview', [
            'categories' => ['All'],
        ]));
        $allResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Supplies/ExpiringReport')
            ->where('scopeName', '')
            ->where('expiringSupplies', function ($expiring) {
                $this->assertSame('drmeds', $expiring['categories'][0]['category_code']);
                $this->assertSame('mssup', $expiring['categories'][1]['category_code']);

                $drmedsArticles = array_column($expiring['categories'][0]['items'], 'article');
                $this->assertSame([
                    'Aardvark Saline',
                    'Amoxicillin 500mg',
                    'Betamethasone Cream',
                    'Paracetamol IV',
                ], $drmedsArticles);
                return true;
            })
        );

        // Filter preview by Division 1
        $div1Response = $this->actingAs($this->superadmin)->get(route('supplies.expiring_report.preview', [
            'categories' => ['All'],
            'division_id' => $this->division1->id,
        ]));
        $div1Response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Supplies/ExpiringReport')
            ->where('scopeName', 'Division: Medical Division')
            ->where('expiringSupplies.total_items_count', 2)
            ->where('expiringSupplies.categories.0.category_code', 'drmeds')
        );

        // Filter preview by Division 2 and Area 2
        $area2Response = $this->actingAs($this->superadmin)->get(route('supplies.expiring_report.preview', [
            'categories' => ['All'],
            'division_id' => $this->division2->id,
            'area_id' => $this->area2->id,
        ]));
        $area2Response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Supplies/ExpiringReport')
            ->where('scopeName', 'Division: Administrative Division | Area: Supply Room')
            ->where('expiringSupplies.total_items_count', 3)
            ->where('expiringSupplies', function ($expiring) {
                $drmedsArticles = array_column($expiring['categories'][0]['items'], 'article');
                $this->assertSame(['Aardvark Saline', 'Betamethasone Cream'], $drmedsArticles);
                return true;
            })
        );

        // Save division-scoped and area-scoped expiring reports
        $saveDivResponse = $this->actingAs($this->superadmin)->postJson(route('supplies.expiring_report.save'), [
            'categories' => ['All'],
            'status_filter' => 'all',
            'division_id' => $this->division1->id,
        ]);
        $saveDivResponse->assertOk();
        $divReport = SupplyReport::findOrFail($saveDivResponse->json('id'));
        $this->assertSame('Division', $divReport->report_type);
        $this->assertSame($this->division1->id, $divReport->scope_id);

        $saveAreaResponse = $this->actingAs($this->superadmin)->postJson(route('supplies.expiring_report.save'), [
            'categories' => ['drmeds'],
            'status_filter' => 'all',
            'division_id' => $this->division2->id,
            'area_id' => $this->area2->id,
        ]);
        $saveAreaResponse->assertOk();
        $areaReport = SupplyReport::findOrFail($saveAreaResponse->json('id'));
        $this->assertSame('Area', $areaReport->report_type);
        $this->assertSame($this->area2->id, $areaReport->scope_id);

        // Viewing saved area report displays scopeName
        $this->actingAs($this->superadmin)->get(route('supplies.report.show', $areaReport->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Supplies/ExpiringReport')
                ->where('scopeName', 'Division: Administrative Division | Area: Supply Room')
                ->where('savedReportId', $areaReport->id)
            );
    }
}
