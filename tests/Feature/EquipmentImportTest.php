<?php

namespace Tests\Feature;

use App\Domain\Equipment\Models\Equipment;
use App\Models\Area;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EquipmentImportTest extends TestCase
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

    public function test_equipment_csv_import_calculates_total_value_and_shortage_overage_correctly(): void
    {
        $csvHeader = "category,article,description,date_acquired,property_number,serial_number,unit_of_measure,unit_value,quantity_per_property_card,quantity_per_physical_count,remarks,end_user,status,division_id,area_id";
        $csvHint = "Hint: Category,Hint: Article,Hint: Description,Hint: Date Acquired,Hint: Property No,Hint: Serial No,Hint: Unit,Hint: Value,Hint: Card Qty,Hint: Physical Qty,Hint: Remarks,Hint: End User,Hint: Status,Hint: Div ID,Hint: Area ID";
        $csvRow = "IT Equipment,Laptop,Dell XPS 15,2026-01-01,PROP-001,SN-12345,unit,50000.00,10,8,Good,User A,Active,{$this->division->id},{$this->area->id}";

        $content = "{$csvHeader}\n{$csvHint}\n{$csvRow}";
        $file = UploadedFile::fake()->createWithContent('equipment.csv', $content);

        $response = $this->actingAs($this->admin)->post(route('equipment.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('equipment.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('equipment', [
            'serial_number' => 'SN-12345',
            'unit_value' => 50000.00,
            'quantity_per_property_card' => 10,
            'quantity_per_physical_count' => 8,
            'total_value' => 400000.00,
            'shortage_overage_qty' => 2,
            'shortage_overage_value' => 100000.00,
        ]);
    }
}
