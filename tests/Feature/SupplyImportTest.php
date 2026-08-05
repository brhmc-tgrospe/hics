<?php

namespace Tests\Feature;

use App\Domain\Supplies\Models\Supply;
use App\Models\Area;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SupplyImportTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $division;
    protected $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->division = Division::create(['div_name' => 'Main Division', 'div_code' => 'DIV-01']);
        $this->area = Area::create(['area_name' => 'Main Area', 'division_id' => $this->division->id]);

        $this->admin = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $this->admin->assignRole('Superadmin');
    }

    public function test_supply_csv_import_calculates_total_amount_and_shortage_overage_correctly(): void
    {
        $csvHeader = "category,article,description,stock_number,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id,expiry_date";
        $csvHint = "Hint: Category,Hint: Article,Hint: Description,Hint: Stock No,Hint: Unit,Hint: Value,Hint: Card Balance,Hint: Count,Hint: Status,Hint: Div ID,Hint: Area ID,Hint: Expiry Date";
        $csvRow = "Medical Supplies,Syringe,10ml syringe,STK-001,box,25.50,100,90,Active,{$this->division->id},{$this->area->id},2027-12-31";

        $content = "{$csvHeader}\n{$csvHint}\n{$csvRow}";
        $file = UploadedFile::fake()->createWithContent('supplies.csv', $content);

        $response = $this->actingAs($this->admin)->post(route('supplies.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('supplies.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('supplies', [
            'stock_number' => 'STK-001',
            'unit_value' => 25.50,
            'balance_per_card' => 100,
            'on_hand_per_count' => 90,
            'total_amount' => 2295.00,
            'shortage_overage_qty' => 10,
            'shortage_overage_value' => 255.00,
        ]);
    }
}
