<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Division;
use App\Models\Area;
use App\Domain\Shared\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

use Database\Seeders\RolesAndPermissionsSeeder;

class SupplyTest extends TestCase
{
    use RefreshDatabase;

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

        Category::create(['code' => 'foodsupplies', 'type' => 'supply', 'name' => 'FOOD SUPPLIES']);
        Category::create(['code' => 'nonfoodsupplies', 'type' => 'supply', 'name' => 'NON-FOOD SUPPLIES']);
        Category::create(['code' => 'mssup', 'type' => 'supply', 'name' => 'MEDICAL AND SURGICAL SUPPLIES']);
    }

    public function test_supply_store_requires_expiry_for_food_supplies()
    {
        $response = $this->actingAs($this->user)->post(route('supplies.store'), [
            'category' => 'foodsupplies',
            'article' => 'Apple',
            'description' => 'Fresh Apple',
            'unit_value' => 10,
            'balance_per_card' => 100,
            'on_hand_per_count' => 100,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
            // no expiry_date
        ]);

        $response->assertSessionHasErrors(['expiry_date']);
    }

    public function test_supply_store_does_not_require_expiry_for_non_food_supplies()
    {
        $response = $this->actingAs($this->user)->post(route('supplies.store'), [
            'category' => 'nonfoodsupplies',
            'article' => 'Soap',
            'description' => 'Cleaning Soap',
            'unit_value' => 5,
            'balance_per_card' => 50,
            'on_hand_per_count' => 50,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
            // no expiry_date
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('supplies', ['article' => 'Soap', 'category' => 'nonfoodsupplies']);
    }

    public function test_supply_csv_import_requires_expiry_for_food_supplies()
    {
        $csvContent = "category,article,description,stock_number,expiry_date,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id\n";
        $csvContent .= "foodsupplies,Apple,Fresh Apple,,,pcs,10,100,100,Available,{$this->division->id},{$this->area->id}\n";
        
        $file = UploadedFile::fake()->createWithContent('supplies.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('supplies.import'), [
            'file' => $file
        ]);

        $response->assertSessionHasErrors(['file' => 'Upload Failed. Line 2: Expiry date is required for the specified category.']);
    }

    public function test_supply_csv_import_does_not_require_expiry_for_non_food_supplies()
    {
        $csvContent = "category,article,description,stock_number,expiry_date,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id\n";
        $csvContent .= "nonfoodsupplies,Soap,Cleaning Soap,,,pcs,5,50,50,Available,{$this->division->id},{$this->area->id}\n";
        
        $file = UploadedFile::fake()->createWithContent('supplies.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('supplies.import'), [
            'file' => $file
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('supplies', ['article' => 'Soap', 'category' => 'nonfoodsupplies']);
    }
}
