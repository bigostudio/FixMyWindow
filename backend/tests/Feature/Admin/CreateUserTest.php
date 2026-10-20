<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(User $user): string
    {
        return JWTAuth::customClaims(['guard' => 'admin'])->fromUser($user);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'     => 'Jane Smith',
            'email'    => 'jane@fixmywindow.in',
            'phone'    => '9123456789',
            'password' => 'Secret@123',
            'role'     => Role::Surveyor->value,
        ], $overrides);
    }

    // ─── POST /api/v1/admin/users ─────────────────────────────────────

    /** @test */
    public function super_admin_can_create_a_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $token = $this->actingAsAdmin($admin);

        $response = $this->postJson('/api/v1/admin/users', $this->validPayload(), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.email', 'jane@fixmywindow.in')
            ->assertJsonPath('data.role', Role::Surveyor->value);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@fixmywindow.in',
            'role'  => Role::Surveyor->value,
        ]);
    }

    /** @test */
    public function ops_admin_can_create_a_user(): void
    {
        $admin = User::factory()->create(['role' => Role::OpsAdmin]);
        $token = $this->actingAsAdmin($admin);

        $response = $this->postJson('/api/v1/admin/users', $this->validPayload(), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function project_manager_cannot_create_a_user(): void
    {
        $pm    = User::factory()->create(['role' => Role::ProjectManager]);
        $token = $this->actingAsAdmin($pm);

        $response = $this->postJson('/api/v1/admin/users', $this->validPayload(), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'jane@fixmywindow.in']);

        $admin = User::factory()->superAdmin()->create();
        $token = $this->actingAsAdmin($admin);

        $response = $this->postJson('/api/v1/admin/users', $this->validPayload(), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_requires_all_fields(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $token = $this->actingAsAdmin($admin);

        $response = $this->postJson('/api/v1/admin/users', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('data.errors.name', fn ($v) => count($v) > 0)
            ->assertJsonPath('data.errors.email', fn ($v) => count($v) > 0)
            ->assertJsonPath('data.errors.password', fn ($v) => count($v) > 0)
            ->assertJsonPath('data.errors.role', fn ($v) => count($v) > 0);
    }

    /** @test */
    public function it_rejects_invalid_role(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $token = $this->actingAsAdmin($admin);

        $response = $this->postJson('/api/v1/admin/users', $this->validPayload(['role' => 'god_mode']), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('data.errors.role', fn ($v) => count($v) > 0);
    }

    /** @test */
    public function it_rejects_weak_password(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $token = $this->actingAsAdmin($admin);

        $response = $this->postJson('/api/v1/admin/users', $this->validPayload(['password' => '1234']), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('data.errors.password', fn ($v) => count($v) > 0);
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/admin/users', $this->validPayload());

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }
}
