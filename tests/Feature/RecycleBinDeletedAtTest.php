<?php

namespace Tests\Feature;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Models\EquipmentReport;
use App\Domain\Shared\Models\Category;
use App\Domain\Supplies\Models\Supply;
use App\Domain\Supply\Models\SupplyReport;
use App\Models\Area;
use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecycleBinDeletedAtTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private Division $division;
    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Superadmin', 'web');
        $this->superadmin = User::factory()->create();
        $this->superadmin->assignRole('Superadmin');

        $this->division = Division::create([
            'div_code' => 'MED',
            'div_name' => 'Medical Division',
        ]);

        $this->area = Area::create([
            'area_code' => 'ER',
            'area_name' => 'Emergency Room',
            'division_id' => $this->division->id,
        ]);
    }

    public function test_recycle_bin_returns_deleted_at_and_orders_descending_across_all_tabs(): void
    {
        $olderCategory = Category::create(['code' => 'oldcat', 'name' => 'Old Category', 'type' => 'equipment']);
        $newerCategory = Category::create(['code' => 'newcat', 'name' => 'New Category', 'type' => 'equipment']);
        $olderCategory->delete();
        $newerCategory->delete();
        Category::withTrashed()->whereKey($olderCategory->id)->update(['deleted_at' => Carbon::parse('2026-10-01 08:00:00')]);
        Category::withTrashed()->whereKey($newerCategory->id)->update(['deleted_at' => Carbon::parse('2026-11-20 15:30:00')]);

        $equipment = Equipment::create([
            'category' => 'medical',
            'article' => 'Deleted Monitor',
            'description' => 'Test monitor',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $equipment->delete();

        $supply = Supply::create([
            'category' => 'mssup',
            'article' => 'Deleted Gauze',
            'description' => 'Test gauze',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $supply->delete();

        $supplyReport = SupplyReport::create([
            'category' => 'mssup',
            'date_of_accountability' => '2026-11-01',
            'year_of_report' => 2026,
            'file_path' => 'reports/supply.json',
            'report_type' => 'General',
            'user_id' => $this->superadmin->id,
        ]);
        $supplyReport->delete();

        $equipmentReport = EquipmentReport::create([
            'category' => 'medical',
            'date_of_accountability' => '2026-11-01',
            'year_of_report' => 2026,
            'file_path' => 'reports/equipment.json',
            'report_type' => 'General',
            'user_id' => $this->superadmin->id,
        ]);
        $equipmentReport->delete();

        $trashedUser = User::factory()->create();
        $trashedUser->delete();

        $trashedDivision = Division::create(['div_code' => 'SURG', 'div_name' => 'Surgery']);
        $trashedDivision->delete();

        $trashedArea = Area::create(['area_code' => 'OR', 'area_name' => 'Operating Room', 'division_id' => $this->division->id]);
        $trashedArea->delete();

        $this->actingAs($this->superadmin)
            ->get(route('recycle-bin.index', ['tab' => 'categories']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RecycleBin/Index')
                ->where('data.data.0.code', 'newcat')
                ->where('data.data.1.code', 'oldcat')
                ->has('data.data.0.deleted_at')
                ->where('data.data.0.deleted_at', fn ($val) => !empty($val))
            );

        foreach (['equipment', 'supplies', 'reports', 'users', 'divisions', 'areas'] as $tab) {
            $this->actingAs($this->superadmin)
                ->get(route('recycle-bin.index', ['tab' => $tab]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('RecycleBin/Index')
                    ->where('tab', $tab)
                    ->has('data.data.0.deleted_at')
                    ->where('data.data.0.deleted_at', fn ($val) => !empty($val))
                );
        }
    }

    public function test_recycle_bin_pagination_links_preserve_tab_and_per_page(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $equipment = Equipment::create([
                'category' => 'medical',
                'article' => "Equipment {$i}",
                'description' => "Test equipment {$i}",
                'division_id' => $this->division->id,
                'area_id' => $this->area->id,
            ]);
            $equipment->delete();
        }

        $nextEquipmentUrl = null;
        $this->actingAs($this->superadmin)
            ->get(route('recycle-bin.index', ['tab' => 'equipment', 'per_page' => 10]))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$nextEquipmentUrl) {
                $page->component('RecycleBin/Index')
                    ->where('tab', 'equipment')
                    ->where('data.next_page_url', function ($url) use (&$nextEquipmentUrl) {
                        $nextEquipmentUrl = $url;
                        return is_string($url)
                            && str_contains($url, 'tab=equipment')
                            && str_contains($url, 'per_page=10')
                            && str_contains($url, 'page=2');
                    });
            });

        $this->actingAs($this->superadmin)
            ->get($nextEquipmentUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RecycleBin/Index')
                ->where('tab', 'equipment')
                ->where('data.current_page', 2)
                ->has('data.data', 5)
            );

        for ($i = 1; $i <= 12; $i++) {
            $supply = Supply::create([
                'category' => 'mssup',
                'article' => "Supply {$i}",
                'description' => "Test supply {$i}",
                'division_id' => $this->division->id,
                'area_id' => $this->area->id,
            ]);
            $supply->delete();
        }

        $nextSupplyUrl = null;
        $this->actingAs($this->superadmin)
            ->get(route('recycle-bin.index', ['tab' => 'supplies', 'per_page' => 10]))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$nextSupplyUrl) {
                $page->component('RecycleBin/Index')
                    ->where('tab', 'supplies')
                    ->where('data.next_page_url', function ($url) use (&$nextSupplyUrl) {
                        $nextSupplyUrl = $url;
                        return is_string($url)
                            && str_contains($url, 'tab=supplies')
                            && str_contains($url, 'per_page=10')
                            && str_contains($url, 'page=2');
                    });
            });

        $this->actingAs($this->superadmin)
            ->get($nextSupplyUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RecycleBin/Index')
                ->where('tab', 'supplies')
                ->where('data.current_page', 2)
                ->has('data.data', 2)
            );
    }
}
