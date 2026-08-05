<?php

namespace Tests\Feature;

use App\Domain\Shared\Models\Category;
use App\Models\Area;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $division = Division::create(['div_name' => 'Main Division', 'div_code' => 'DIV-01']);
        $area = Area::create(['area_name' => 'Main Area', 'division_id' => $division->id]);

        $this->admin = User::factory()->create([
            'division_id' => $division->id,
            'area_id' => $area->id,
        ]);
        $this->admin->assignRole('Superadmin');

        $this->regularUser = User::factory()->create([
            'division_id' => $division->id,
            'area_id' => $area->id,
        ]);
        $this->regularUser->assignRole('Encoder');
    }

    public function test_categories_index_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->admin)->get(route('categories.index'));

        $response->assertStatus(200);
    }

    public function test_equipment_category_can_be_created_with_auto_generated_code(): void
    {
        $response = $this->actingAs($this->admin)->post(route('categories.store'), [
            'type' => 'equipment',
            'name' => 'Information and Communication Technology',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Information and Communication Technology',
            'type' => 'equipment',
            'code' => 'information_and_communication_technology',
        ]);
    }

    public function test_supply_category_can_be_created(): void
    {
        $response = $this->actingAs($this->admin)->post(route('categories.store'), [
            'type' => 'supply',
            'name' => 'Medical and Surgical Supplies',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Medical and Surgical Supplies',
            'type' => 'supply',
            'code' => 'medical_and_surgical_supplies',
        ]);
    }

    public function test_category_with_duplicate_name_generates_unique_code(): void
    {
        $this->actingAs($this->admin)->post(route('categories.store'), [
            'type' => 'equipment',
            'name' => 'Dental Equipment',
        ]);

        $response2 = $this->actingAs($this->admin)->post(route('categories.store'), [
            'type' => 'equipment',
            'name' => 'Dental Equipment',
        ]);

        $response2->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Dental Equipment',
            'code' => 'dental_equipment',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Dental Equipment',
            'code' => 'dental_equipment_1',
        ]);
    }

    public function test_category_creation_fails_validation_for_missing_name_or_invalid_type(): void
    {
        $response = $this->actingAs($this->admin)->post(route('categories.store'), [
            'type' => 'invalid_type',
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'type']);
    }

    public function test_unauthorized_user_cannot_create_category(): void
    {
        $response = $this->actingAs($this->regularUser)->post(route('categories.store'), [
            'type' => 'equipment',
            'name' => 'Unauthorized Category',
        ]);

        $response->assertStatus(403);
    }

    public function test_categories_can_be_bulk_deleted(): void
    {
        $cat1 = Category::create(['name' => 'Cat 1', 'code' => 'cat_1', 'type' => 'equipment']);
        $cat2 = Category::create(['name' => 'Cat 2', 'code' => 'cat_2', 'type' => 'supply']);

        $response = $this->actingAs($this->admin)->delete(route('categories.bulk_delete'), [
            'ids' => [$cat1->id, $cat2->id],
        ]);

        $response->assertRedirect();
        $this->assertSoftDeleted('categories', ['id' => $cat1->id]);
        $this->assertSoftDeleted('categories', ['id' => $cat2->id]);
    }
}
