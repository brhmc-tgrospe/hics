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
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SupplyImportStockNumberTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;
    protected Area $area;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_supplies', 'create_supplies', 'edit_supplies', 'delete_supplies',
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo($permissions);

        $this->division = Division::create(['div_code' => 'GSD', 'div_name' => 'General Services Division']);
        $this->area = Area::create([
            'area_name' => 'Warehouse',
            'area_code' => 'WH',
            'division_id' => $this->division->id,
        ]);

        $this->adminUser = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $this->adminUser->assignRole('Admin');
    }

    // ==========================================
    // 1. Direct Domain Action Tests
    // ==========================================

    public function test_import_with_empty_stock_number_updates_when_category_article_and_description_match(): void
    {
        $action = new ImportSupplyAction(new CreateSupplyAction(), new UpdateSupplyAction());

        $dto1 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'stock_number' => null,
            'unit_of_measure' => 'ream',
            'unit_value' => 250.00,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result1 = $action->execute($dto1);
        $this->assertEquals('created', $result1['action']);
        $this->assertCount(1, Supply::all());

        // Re-import matching category, article, and description without stock number
        $dto2 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Bond Paper ', // whitespace/casing tolerance
            'description' => 'a4 70gsm',
            'stock_number' => '',
            'unit_of_measure' => 'ream',
            'unit_value' => 280.00,
            'balance_per_card' => 15,
            'on_hand_per_count' => 15,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('updated', $result2['action']);
        $this->assertCount(1, Supply::all());
        $this->assertEquals($result1['record']->id, $result2['record']->id);

        $fresh = Supply::find($result1['record']->id);
        $this->assertEquals(15, $fresh->on_hand_per_count);
        $this->assertEquals(280.00, (float)$fresh->unit_value);
    }

    public function test_import_with_empty_stock_number_creates_new_when_article_differs(): void
    {
        $action = new ImportSupplyAction(new CreateSupplyAction(), new UpdateSupplyAction());

        $dto1 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'stock_number' => null,
            'unit_value' => 250.00,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $action->execute($dto1);

        // Different article, same category and description
        $dto2 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Special Paper',
            'description' => 'A4 70gsm',
            'stock_number' => null,
            'unit_value' => 350.00,
            'balance_per_card' => 5,
            'on_hand_per_count' => 5,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('created', $result2['action']);
        $this->assertCount(2, Supply::all());
    }

    public function test_import_with_empty_stock_number_creates_new_when_description_differs(): void
    {
        $action = new ImportSupplyAction(new CreateSupplyAction(), new UpdateSupplyAction());

        $dto1 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'stock_number' => null,
            'unit_value' => 250.00,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $action->execute($dto1);

        // Different description (A4 80gsm vs A4 70gsm)
        $dto2 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 80gsm',
            'stock_number' => null,
            'unit_value' => 270.00,
            'balance_per_card' => 8,
            'on_hand_per_count' => 8,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('created', $result2['action']);
        $this->assertCount(2, Supply::all());
    }

    public function test_import_with_empty_stock_number_creates_new_when_category_differs(): void
    {
        $action = new ImportSupplyAction(new CreateSupplyAction(), new UpdateSupplyAction());

        $dto1 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Disinfectant Spray',
            'description' => '500ml aerosol',
            'stock_number' => null,
            'unit_value' => 150.00,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $action->execute($dto1);

        // Different category
        $dto2 = SupplyDTO::fromArray([
            'category' => 'janitorial',
            'article' => 'Disinfectant Spray',
            'description' => '500ml aerosol',
            'stock_number' => null,
            'unit_value' => 150.00,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('created', $result2['action']);
        $this->assertCount(2, Supply::all());
    }

    public function test_import_with_empty_stock_number_throws_exception_on_ambiguous_multiple_matches(): void
    {
        Supply::create([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'stock_number' => null,
            'unit_value' => 250.00,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        Supply::create([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'stock_number' => null,
            'unit_value' => 260.00,
            'balance_per_card' => 5,
            'on_hand_per_count' => 5,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $action = new ImportSupplyAction(new CreateSupplyAction(), new UpdateSupplyAction());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Multiple existing records match Category 'officesup', Article 'Bond Paper'");

        $dto = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'stock_number' => null,
            'unit_value' => 270.00,
            'balance_per_card' => 20,
            'on_hand_per_count' => 20,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $action->execute($dto);
    }

    public function test_import_with_same_stock_number_updates_existing_record(): void
    {
        $action = new ImportSupplyAction(new CreateSupplyAction(), new UpdateSupplyAction());

        $dto1 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Ballpen',
            'description' => 'Black 0.5',
            'stock_number' => 'STK-PEN-001',
            'unit_of_measure' => 'piece',
            'unit_value' => 10.00,
            'balance_per_card' => 50,
            'on_hand_per_count' => 50,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result1 = $action->execute($dto1);
        $this->assertEquals('created', $result1['action']);
        $this->assertCount(1, Supply::all());
        $this->assertEquals('STK-PEN-001', $result1['record']->stock_number);

        // Re-import with same stock number
        $dto2 = SupplyDTO::fromArray([
            'category' => 'officesup',
            'article' => 'Ballpen',
            'description' => 'Black 0.5',
            'stock_number' => 'STK-PEN-001',
            'unit_of_measure' => 'piece',
            'unit_value' => 12.00,
            'balance_per_card' => 70,
            'on_hand_per_count' => 70,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $result2 = $action->execute($dto2);
        $this->assertEquals('updated', $result2['action']);
        $this->assertCount(1, Supply::all());
        $this->assertEquals($result1['record']->id, $result2['record']->id);

        $fresh = Supply::find($result1['record']->id);
        $this->assertEquals(70, $fresh->on_hand_per_count);
        $this->assertEquals(12.00, (float)$fresh->unit_value);
    }

    // ==========================================
    // 2. Full HTTP CSV File Upload Route Tests
    // ==========================================

    public function test_http_csv_normal_import_creates_items(): void
    {
        $csvContent = implode("\n", [
            'category,article,description,stock_number,expiry_date,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id',
            'Hint: Category Code,Name,Desc,Stock,YYYY-MM-DD,UOM,Val,Card,Count,Status,Div,Area',
            "officesup,Stapler,Heavy Duty Standard,STK-STAPLER-01,,piece,120.00,5,5,Available,{$this->division->id},{$this->area->id}",
            "officesup,Ruler,Plastic 30cm,,,piece,15.00,20,20,Available,{$this->division->id},{$this->area->id}",
        ]);

        $file = UploadedFile::fake()->createWithContent('supplies_normal.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)->post(route('supplies.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('supplies.index'));
        $this->assertDatabaseCount('supplies', 2);
        $this->assertDatabaseHas('supplies', [
            'article' => 'Stapler',
            'stock_number' => 'STK-STAPLER-01',
            'unit_value' => 120.00,
        ]);
        $this->assertDatabaseHas('supplies', [
            'article' => 'Ruler',
            'stock_number' => null,
            'unit_value' => 15.00,
        ]);
    }

    public function test_http_csv_import_same_article_but_different_description_creates_two_separate_items(): void
    {
        // 1st CSV import: Bond Paper with A4 70gsm description
        $csv1 = implode("\n", [
            'category,article,description,stock_number,expiry_date,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id',
            'Hint: Category Code,Name,Desc,Stock,YYYY-MM-DD,UOM,Val,Card,Count,Status,Div,Area',
            "officesup,Bond Paper,A4 70gsm,,,ream,250.00,10,10,Available,{$this->division->id},{$this->area->id}",
        ]);

        $file1 = UploadedFile::fake()->createWithContent('first_import.csv', $csv1);
        $res1 = $this->actingAs($this->adminUser)->post(route('supplies.import'), ['file' => $file1]);
        $res1->assertRedirect(route('supplies.index'));
        $this->assertDatabaseCount('supplies', 1);

        // 2nd CSV import: Same Category ('officesup') and same Article ('Bond Paper'), BUT DIFFERENT description ('A4 80gsm')
        $csv2 = implode("\n", [
            'category,article,description,stock_number,expiry_date,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id',
            'Hint: Category Code,Name,Desc,Stock,YYYY-MM-DD,UOM,Val,Card,Count,Status,Div,Area',
            "officesup,Bond Paper,A4 80gsm,,,ream,280.00,20,20,Available,{$this->division->id},{$this->area->id}",
        ]);

        $file2 = UploadedFile::fake()->createWithContent('second_import.csv', $csv2);
        $res2 = $this->actingAs($this->adminUser)->post(route('supplies.import'), ['file' => $file2]);
        $res2->assertRedirect(route('supplies.index'));

        // Result: Both items must exist as separate items!
        $this->assertDatabaseCount('supplies', 2);
        $this->assertDatabaseHas('supplies', [
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'unit_value' => 250.00,
        ]);
        $this->assertDatabaseHas('supplies', [
            'article' => 'Bond Paper',
            'description' => 'A4 80gsm',
            'unit_value' => 280.00,
        ]);
    }

    public function test_http_csv_import_same_category_article_and_description_updates_existing_item(): void
    {
        // 1st CSV import: Initial creation of Ballpen Black
        $csv1 = implode("\n", [
            'category,article,description,stock_number,expiry_date,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id',
            'Hint: Category Code,Name,Desc,Stock,YYYY-MM-DD,UOM,Val,Card,Count,Status,Div,Area',
            "officesup,Ballpen,Black 0.5,,,piece,10.00,30,30,Available,{$this->division->id},{$this->area->id}",
        ]);

        $file1 = UploadedFile::fake()->createWithContent('ballpen_initial.csv', $csv1);
        $res1 = $this->actingAs($this->adminUser)->post(route('supplies.import'), ['file' => $file1]);
        $res1->assertRedirect(route('supplies.index'));
        $this->assertDatabaseCount('supplies', 1);

        $initial = Supply::first();
        $this->assertEquals(10.00, (float)$initial->unit_value);
        $this->assertEquals(30, $initial->on_hand_per_count);

        // 2nd CSV import: Re-import with exact same Category, Article, and Description (NO stock number), but updated price & count
        $csv2 = implode("\n", [
            'category,article,description,stock_number,expiry_date,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id',
            'Hint: Category Code,Name,Desc,Stock,YYYY-MM-DD,UOM,Val,Card,Count,Status,Div,Area',
            "officesup,Ballpen,Black 0.5,,,piece,15.00,75,75,Available,{$this->division->id},{$this->area->id}",
        ]);

        $file2 = UploadedFile::fake()->createWithContent('ballpen_updated.csv', $csv2);
        $res2 = $this->actingAs($this->adminUser)->post(route('supplies.import'), ['file' => $file2]);
        $res2->assertRedirect(route('supplies.index'));

        // Result: Exactly 1 record remains in DB and its values are updated!
        $this->assertDatabaseCount('supplies', 1);
        $updated = Supply::first();
        $this->assertEquals($initial->id, $updated->id);
        $this->assertEquals(15.00, (float)$updated->unit_value);
        $this->assertEquals(75, $updated->on_hand_per_count);
    }

    // ==========================================
    // 3. HTTP Error Handling & Safety Tests
    // ==========================================

    public function test_http_csv_import_fails_with_line_number_when_ambiguous_duplicate_matches_exist(): void
    {
        // Setup two existing identical items (no stock number)
        Supply::create([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'stock_number' => null,
            'unit_value' => 250.00,
            'balance_per_card' => 10,
            'on_hand_per_count' => 10,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        Supply::create([
            'category' => 'officesup',
            'article' => 'Bond Paper',
            'description' => 'A4 70gsm',
            'stock_number' => null,
            'unit_value' => 260.00,
            'balance_per_card' => 5,
            'on_hand_per_count' => 5,
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $this->assertDatabaseCount('supplies', 2);

        // Upload CSV attempting to update without stock number (Line 3 is the data row)
        $csv = implode("\n", [
            'category,article,description,stock_number,expiry_date,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id',
            'Hint: Category Code,Name,Desc,Stock,YYYY-MM-DD,UOM,Val,Card,Count,Status,Div,Area',
            "officesup,Bond Paper,A4 70gsm,,,ream,300.00,20,20,Available,{$this->division->id},{$this->area->id}",
        ]);

        $file = UploadedFile::fake()->createWithContent('ambiguous_import.csv', $csv);
        $response = $this->actingAs($this->adminUser)->post(route('supplies.import'), ['file' => $file]);

        // Must fail with validation error containing the exact line and disambiguation guidance
        $response->assertSessionHasErrors('file');
        $errorMsg = session('errors')->first('file');
        $this->assertStringContainsString('Upload Failed. Line 3:', $errorMsg);
        $this->assertStringContainsString("Multiple existing records match Category 'officesup', Article 'Bond Paper'", $errorMsg);
        $this->assertStringContainsString('Please assign a unique Stock Number to update', $errorMsg);

        // Database records remain safe and untouched
        $this->assertDatabaseCount('supplies', 2);
    }

    public function test_http_csv_import_fails_with_line_number_when_unit_value_is_invalid(): void
    {
        // CSV with unit_value = 0 on Line 3
        $csv = implode("\n", [
            'category,article,description,stock_number,expiry_date,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id',
            'Hint: Category Code,Name,Desc,Stock,YYYY-MM-DD,UOM,Val,Card,Count,Status,Div,Area',
            "officesup,Correction Tape,5mm x 6m,,,piece,0,10,10,Available,{$this->division->id},{$this->area->id}",
        ]);

        $file = UploadedFile::fake()->createWithContent('invalid_value.csv', $csv);
        $response = $this->actingAs($this->adminUser)->post(route('supplies.import'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
        $errorMsg = session('errors')->first('file');
        $this->assertStringContainsString('Upload Failed. Line 3:', $errorMsg);
        $this->assertStringContainsString('unit value field must be greater than 0', strtolower($errorMsg));
    }

    public function test_http_csv_import_fails_with_line_number_when_unauthorized_division_is_targeted(): void
    {
        $encoderRole = Role::firstOrCreate(['name' => 'Encoder']);
        $encoderRole->givePermissionTo(['view_supplies', 'create_supplies', 'edit_supplies']);

        $encoderUser = User::factory()->create([
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $encoderUser->assignRole('Encoder');

        $otherDivision = Division::create(['div_code' => 'OTHER', 'div_name' => 'Other Division']);

        // CSV specifies other division ID on Line 3
        $csv = implode("\n", [
            'category,article,description,stock_number,expiry_date,unit_of_measure,unit_value,balance_per_card,on_hand_per_count,status,division_id,area_id',
            'Hint: Category Code,Name,Desc,Stock,YYYY-MM-DD,UOM,Val,Card,Count,Status,Div,Area',
            "officesup,Paper Clips,Vinyl Coated,,,box,25.00,10,10,Available,{$otherDivision->id},{$this->area->id}",
        ]);

        $file = UploadedFile::fake()->createWithContent('unauthorized_division.csv', $csv);
        $response = $this->actingAs($encoderUser)->post(route('supplies.import'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
        $errorMsg = session('errors')->first('file');
        $this->assertStringContainsString('Upload Failed. Line 3: You are only allowed to upload data for your assigned division', $errorMsg);
    }
}
