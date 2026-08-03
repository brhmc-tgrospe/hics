<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserValidationTest extends TestCase
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

    public function test_user_creation_fails_when_username_is_already_in_use(): void
    {
        User::factory()->create([
            'username' => 'existinguser',
            'email' => 'other@example.com',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'existinguser',
            'email' => 'new@example.com',
            'contact_number' => '1234567890',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
            'role' => 'Encoder',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'username' => 'The username is already in use.',
        ]);
    }

    public function test_user_creation_fails_when_email_is_already_in_use(): void
    {
        User::factory()->create([
            'username' => 'anotheruser',
            'email' => 'existing@example.com',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'newuser',
            'email' => 'existing@example.com',
            'contact_number' => '1234567890',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
            'role' => 'Encoder',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'The email is already in use.',
        ]);
    }

    public function test_user_creation_fails_when_password_is_under_6_characters(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'uniqueuser',
            'email' => 'unique@example.com',
            'contact_number' => '1234567890',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
            'role' => 'Encoder',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'The password must be at least 6 characters.',
        ]);
    }

    public function test_user_creation_fails_when_passwords_do_not_match(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'uniqueuser',
            'email' => 'unique@example.com',
            'contact_number' => '1234567890',
            'division_id' => $this->division->id,
            'area_id' => $this->area->id,
            'role' => 'Encoder',
            'password' => 'password123',
            'password_confirmation' => 'mismatch123',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'The passwords do not match.',
        ]);
    }
}
