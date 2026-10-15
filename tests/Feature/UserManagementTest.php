<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SystemDefaultsSeeder::class);
    }

    public function test_admin_can_create_operator_user(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Operador municipal',
            'email' => 'operador@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'operator',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'operador@example.com']);
        $operator = User::query()->where('email', 'operador@example.com')->first();
        $this->assertTrue($operator->hasRole('operator'));
    }

    public function test_operator_cannot_access_user_management(): void
    {
        $operator = $this->makeUserWithRole('operator');

        $this->actingAs($operator)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_operator_cannot_pair_screens(): void
    {
        $operator = $this->makeUserWithRole('operator');

        $this->actingAs($operator)
            ->get(route('screens.pair.create'))
            ->assertForbidden();
    }

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        return $user;
    }
}
