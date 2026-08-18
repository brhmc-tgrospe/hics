<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Division;
use App\Models\Area;
use App\Domain\Supplies\Models\Supply;
use App\Domain\Supplies\DTOs\SupplyDTO;
use App\Domain\Supplies\Actions\ImportSupplyAction;
use App\Domain\Supplies\Actions\CreateSupplyAction;
use App\Domain\Supplies\Actions\UpdateSupplyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplyImportStockNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_with_empty_stock_number_always_creates_new_record_even_with_same_article_and_description(): void
    {
        $division = Division::create(['div_name' => 'General Services', 'div_code' => 'GSD']);
        $area = Area::create(['area_name' => 'Warehouse', 'area_code' => 'WH', 'division_id' => $division->id]);

        $action = new ImportSupplyAction(new CreateSupplyAction(), new UpdateSupplyAction());

        // First item with empty stock number
        $dto1 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'stock_number' => null,
            'unit_of_measure' => 'ream',
            'unit_value' => 250.00,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'division_id' => $division->id,
            'area_id' => $area->id,
        ]);

        $result1 = $action->execute($dto1);
        $this->assertEquals('created', $result1['action']);
        $this->assertCount(1, Supply::all());

        // Second item with same article and description, and empty stock number
        $dto2 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'stock_number' => '',
            'unit_of_measure' => 'ream',
            'unit_value' => 260.00,
            'balance_per_card' => 5,
            'on_hand_per_count' => 5,
            'division_id' => $division->id,
            'area_id' => $area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('created', $result2['action']);
        $this->assertCount(2, Supply::all());
        $this->assertNotEquals($result1['record']->id, $result2['record']->id);
    }

    public function test_import_with_same_stock_number_updates_existing_record(): void
    {
        $division = Division::create(['div_name' => 'General Services', 'div_code' => 'GSD']);
        $area = Area::create(['area_name' => 'Warehouse', 'area_code' => 'WH', 'division_id' => $division->id]);

        $action = new ImportSupplyAction(new CreateSupplyAction(), new UpdateSupplyAction());

        // Initial creation with stock number
        $dto1 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Ballpen',
            'description' => 'Black 0.5',
            'stock_number' => 'STK-PEN-001',
            'unit_of_measure' => 'piece',
            'unit_value' => 10.00,
            'balance_per_card' => 50,
            'on_hand_per_count' => 50,
            'division_id' => $division->id,
            'area_id' => $area->id,
        ]);

        $result1 = $action->execute($dto1);
        $this->assertEquals('created', $result1['action']);
        $this->assertCount(1, Supply::all());
        $this->assertEquals('STK-PEN-001', $result1['record']->stock_number);

        // Re-import with same stock number but updated on-hand count
        $dto2 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Ballpen',
            'description' => 'Black 0.5',
            'stock_number' => 'STK-PEN-001',
            'unit_of_measure' => 'piece',
            'unit_value' => 12.00,
            'balance_per_card' => 70,
            'on_hand_per_count' => 70,
            'division_id' => $division->id,
            'area_id' => $area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('updated', $result2['action']);
        $this->assertCount(1, Supply::all());
        $this->assertEquals($result1['record']->id, $result2['record']->id);

        $fresh = Supply::find($result1['record']->id);
        $this->assertEquals(70, $fresh->on_hand_per_count);
        $this->assertEquals(12.00, (float)$fresh->unit_value);
    }
}
