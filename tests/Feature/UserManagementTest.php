<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_users_index_loads_user_area_relationship(): void
    {
        $testUser = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);
        $testUser->assignRole('Encoder');

        $response = $this->actingAs($this->admin)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Index')
            ->has('users.data', fn (Assert $users) => $users
                ->each(fn (Assert $user) => $user
                    ->has('area')
                    ->where('area.area_name', fn ($val) => $val !== null)
                    ->etc()
                )
            )
        );
    }
}
