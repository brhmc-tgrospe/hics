<?php

namespace Tests\Feature\Auth;

use App\Models\Area;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $division;
    protected $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->division = Division::create(['div_name' => 'Main Division', 'div_code' => 'DIV-01']);
        $this->area = Area::create(['area_name' => 'Main Area', 'division_id' => $this->division->id]);
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ], $attributes));
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_email(): void
    {
        $user = $this->createUser([
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'username' => 'john@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_authenticate_using_username(): void
    {
        $user = $this->createUser([
            'email' => 'jane@example.com',
            'username' => 'janedoe',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'username' => 'janedoe',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = $this->createUser([
            'email' => 'user@example.com',
            'username' => 'validuser',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'username' => 'validuser',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
