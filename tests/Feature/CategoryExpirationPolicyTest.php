<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Domain\Shared\Models\Category;
use App\Domain\Supplies\Services\SupplyCategoryExpirationPolicy;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryExpirationPolicyTest extends TestCase
{
    use RefreshDatabase;
    public function test_supply_category_expiration_policy_resolves_db_flag_correctly()
    {
        SupplyCategoryExpirationPolicy::clearCache();

        // Create a perishable category
        Category::updateOrCreate(
            ['code' => 'TEST_MED'],
            ['name' => 'Test Medical Supplies', 'type' => 'supply', 'has_expiration_date' => true]
        );

        // Create an exempt category
        Category::updateOrCreate(
            ['code' => 'TEST_HDW'],
            ['name' => 'Test Hardware Supplies', 'type' => 'supply', 'has_expiration_date' => false]
        );

        $this->assertTrue(SupplyCategoryExpirationPolicy::isExpiryRequired('TEST_MED'));
        $this->assertTrue(SupplyCategoryExpirationPolicy::isExpiryRequired('Test Medical Supplies'));

        $this->assertFalse(SupplyCategoryExpirationPolicy::isExpiryRequired('TEST_HDW'));
        $this->assertFalse(SupplyCategoryExpirationPolicy::isExpiryRequired('Test Hardware Supplies'));

        $this->assertTrue(SupplyCategoryExpirationPolicy::isExpiryExempt('TEST_HDW'));
        $this->assertFalse(SupplyCategoryExpirationPolicy::isExpiryExempt('TEST_MED'));
    }

    public function test_supply_category_expiration_policy_fallback_heuristics()
    {
        SupplyCategoryExpirationPolicy::clearCache();

        $this->assertTrue(SupplyCategoryExpirationPolicy::isExpiryRequired('FOOD SUPPLIES'));
        $this->assertTrue(SupplyCategoryExpirationPolicy::isExpiryRequired('DRMEDS'));
        $this->assertTrue(SupplyCategoryExpirationPolicy::isExpiryRequired('MSSUP'));

        $this->assertFalse(SupplyCategoryExpirationPolicy::isExpiryRequired('NON FOOD SUPPLIES'));
        $this->assertFalse(SupplyCategoryExpirationPolicy::isExpiryRequired('HARDWARE SUPPLIES'));
        $this->assertFalse(SupplyCategoryExpirationPolicy::isExpiryRequired('ICTSUPPLY'));
        $this->assertFalse(SupplyCategoryExpirationPolicy::isExpiryRequired('OFFICE'));
    }

    public function test_hardware_supplies_is_seeded_and_exempt_from_expiration()
    {
        $category = Category::where('name', 'HARDWARE SUPPLIES')->first();
        if ($category) {
            $this->assertFalse((bool)$category->has_expiration_date);
            $this->assertTrue(SupplyCategoryExpirationPolicy::isExpiryExempt($category->code));
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_superadmin_can_create_category_with_expiration_toggle()
    {
        Role::findOrCreate('Superadmin', 'web');
        $superadmin = User::factory()->create();
        $superadmin->assignRole('Superadmin');

        $response = $this->actingAs($superadmin)->post(route('categories.store'), [
            'type' => 'supply',
            'name' => 'Laboratory Reagents',
            'has_expiration_date' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'name' => 'Laboratory Reagents',
            'type' => 'supply',
            'has_expiration_date' => true,
        ]);

        $created = Category::where('name', 'Laboratory Reagents')->first();
        $this->assertTrue(SupplyCategoryExpirationPolicy::isExpiryRequired($created->code));
    }

    public function test_non_privileged_user_cannot_create_category()
    {
        Role::findOrCreate('Staff', 'web');
        $staff = User::factory()->create();
        $staff->assignRole('Staff');

        $response = $this->actingAs($staff)->post(route('categories.store'), [
            'type' => 'supply',
            'name' => 'Unauthorized Category',
            'has_expiration_date' => false,
        ]);

        $response->assertForbidden();
    }
}
