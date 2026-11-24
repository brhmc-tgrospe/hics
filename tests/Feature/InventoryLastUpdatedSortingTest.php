<?php

namespace Tests\Feature;

use App\Domain\Equipment\Actions\GetEquipmentAction;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Supplies\Actions\GetSuppliesAction;
use App\Domain\Supplies\Models\Supply;
use App\Models\Area;
use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InventoryLastUpdatedSortingTest extends TestCase
{
    use RefreshDatabase;

    private Division $division;
    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_equipment_can_be_sorted_by_updated_at(): void
    {
        $older = Equipment::create([
            'category' => 'medical',
            'article' => 'Older Monitor',
            'description' => 'Older item',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        Equipment::whereKey($older->id)->update(['updated_at' => Carbon::parse('2026-01-10 08:00:00')]);

        $newer = Equipment::create([
            'category' => 'medical',
            'article' => 'Newer Monitor',
            'description' => 'Newer item',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        Equipment::whereKey($newer->id)->update(['updated_at' => Carbon::parse('2026-11-15 10:30:00')]);

        $desc = app(GetEquipmentAction::class)->execute([
            'sort_field' => 'updated_at',
            'sort_direction' => 'desc',
        ]);
        $this->assertSame(['Newer Monitor', 'Older Monitor'], $desc->pluck('article')->all());

        $asc = app(GetEquipmentAction::class)->execute([
            'sort_field' => 'updated_at',
            'sort_direction' => 'asc',
        ]);
        $this->assertSame(['Older Monitor', 'Newer Monitor'], $asc->pluck('article')->all());
    }

    public function test_supplies_can_be_sorted_by_updated_at(): void
    {
        $older = Supply::create([
            'category' => 'medical',
            'article' => 'Older Gauze',
            'description' => 'Older supply',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        Supply::whereKey($older->id)->update(['updated_at' => Carbon::parse('2026-02-01 09:00:00')]);

        $newer = Supply::create([
            'category' => 'medical',
            'article' => 'Newer Gauze',
            'description' => 'Newer supply',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        Supply::whereKey($newer->id)->update(['updated_at' => Carbon::parse('2026-11-18 14:45:00')]);

        $desc = app(GetSuppliesAction::class)->execute([
            'sort_field' => 'updated_at',
            'sort_direction' => 'desc',
        ]);
        $this->assertSame(['Newer Gauze', 'Older Gauze'], $desc->pluck('article')->all());

        $asc = app(GetSuppliesAction::class)->execute([
            'sort_field' => 'updated_at',
            'sort_direction' => 'asc',
        ]);
        $this->assertSame(['Older Gauze', 'Newer Gauze'], $asc->pluck('article')->all());
    }
}
