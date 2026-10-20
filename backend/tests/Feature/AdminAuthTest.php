<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── login ───────────────────────────────────────────────────────

    /** @test */
    public function it_logs_in_admin_with_valid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'admin@fmw.in',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email'    => 'admin@fmw.in',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'access_token', 'refresh_token', 'token_type', 'expires_in'],
                'message',
            ]);
    }

    /** @test */
    public function it_rejects_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'admin@fmw.in',
            'password' => Hash::make('correct'),
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email'    => 'admin@fmw.in',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    /** @test */
    public function it_rejects_login_for_inactive_user(): void
    {
        User::factory()->inactive()->create([
            'email'    => 'inactive@fmw.in',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email'    => 'inactive@fmw.in',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    // ─── refresh ─────────────────────────────────────────────────────

    /** @test */
    public function it_refreshes_admin_access_token(): void
    {
        User::factory()->create([
            'email'    => 'admin@fmw.in',
            'password' => Hash::make('secret123'),
        ]);

        $loginResponse = $this->postJson('/api/v1/admin/auth/login', [
            'email'    => 'admin@fmw.in',
            'password' => 'secret123',
        ])->assertStatus(200);

        $refreshToken = $loginResponse->json('data.refresh_token');

        $response = $this->postJson('/api/v1/admin/auth/refresh-token', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['access_token'], 'message']);
    }

    // ─── logout ──────────────────────────────────────────────────────

    /** @test */
    public function it_logs_out_admin(): void
    {
        User::factory()->create([
            'email'    => 'admin@fmw.in',
            'password' => Hash::make('secret123'),
        ]);

        $loginResponse = $this->postJson('/api/v1/admin/auth/login', [
            'email'    => 'admin@fmw.in',
            'password' => 'secret123',
        ])->assertStatus(200);

        $accessToken  = $loginResponse->json('data.access_token');
        $refreshToken = $loginResponse->json('data.refresh_token');

        $response = $this->withHeader('Authorization', "Bearer {$accessToken}")
            ->postJson('/api/v1/admin/auth/logout', ['refresh_token' => $refreshToken]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('refresh_tokens', ['revoked_at' => null]);
    }

    // ─── change-password ─────────────────────────────────────────────

    /** @test */
    public function it_changes_password_successfully(): void
    {
        User::factory()->create([
            'email'    => 'admin@fmw.in',
            'password' => Hash::make('OldPass@1'),
        ]);

        $loginResponse = $this->postJson('/api/v1/admin/auth/login', [
            'email'    => 'admin@fmw.in',
            'password' => 'OldPass@1',
        ])->assertStatus(200);

        $accessToken = $loginResponse->json('data.access_token');

        $response = $this->withHeader('Authorization', "Bearer {$accessToken}")
            ->postJson('/api/v1/admin/auth/change-password', [
                'current_password'          => 'OldPass@1',
                'new_password'              => 'NewPass@2',
                'new_password_confirmation' => 'NewPass@2',
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    /** @test */
    public function it_rejects_change_password_with_wrong_current_password(): void
    {
        User::factory()->create([
            'email'    => 'admin@fmw.in',
            'password' => Hash::make('Correct@1'),
        ]);

        $loginResponse = $this->postJson('/api/v1/admin/auth/login', [
            'email'    => 'admin@fmw.in',
            'password' => 'Correct@1',
        ])->assertStatus(200);

        $accessToken = $loginResponse->json('data.access_token');

        $response = $this->withHeader('Authorization', "Bearer {$accessToken}")
            ->postJson('/api/v1/admin/auth/change-password', [
                'current_password'          => 'Wrong@1',
                'new_password'              => 'NewPass@2',
                'new_password_confirmation' => 'NewPass@2',
            ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }
}
