<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OtpRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── send-otp ────────────────────────────────────────────────────

    /** @test */
    public function it_sends_otp_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', ['phone' => '9876543210']);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'message']);

        $this->assertDatabaseHas('otp_requests', ['phone' => '9876543210']);
    }

    /** @test */
    public function it_rejects_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', ['phone' => '1234567890']);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('data.errors.phone', fn ($v) => count($v) > 0);
    }

    /** @test */
    public function it_returns_429_on_resend_rate_limit(): void
    {
        OtpRequest::factory()->count(3)->create(['phone' => '9876543210']);

        $response = $this->postJson('/api/v1/auth/send-otp', ['phone' => '9876543210']);

        $response->assertStatus(429)->assertJson(['success' => false]);
    }

    /** @test */
    public function it_blocks_send_otp_when_phone_is_locked(): void
    {
        OtpRequest::factory()->create([
            'phone'        => '9876543210',
            'locked_until' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/send-otp', ['phone' => '9876543210']);

        $response->assertStatus(429)->assertJson(['success' => false]);
    }

    // ─── verify-otp ──────────────────────────────────────────────────

    /** @test */
    public function it_verifies_otp_and_creates_new_customer(): void
    {
        OtpRequest::factory()->create([
            'phone'    => '9123456789',
            'otp_hash' => Hash::make('654321'),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9123456789',
            'otp'   => '654321',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['customer', 'access_token', 'refresh_token', 'token_type', 'expires_in'],
                'message',
            ]);

        $this->assertDatabaseHas('customers', ['phone' => '9123456789']);
        $this->assertDatabaseHas('otp_requests', [
            'phone' => '9123456789',
        ]);
        $this->assertNotNull(
            OtpRequest::where('phone', '9123456789')->value('consumed_at')
        );
    }

    /** @test */
    public function it_verifies_otp_and_returns_existing_customer(): void
    {
        Customer::factory()->create(['phone' => '9111111111']);

        OtpRequest::factory()->create([
            'phone'    => '9111111111',
            'otp_hash' => Hash::make('111111'),
        ]);

        $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9111111111',
            'otp'   => '111111',
        ])->assertStatus(200);

        $this->assertDatabaseCount('customers', 1);
    }

    /** @test */
    public function it_rejects_expired_otp(): void
    {
        OtpRequest::factory()->expired()->create([
            'phone'    => '9876543210',
            'otp_hash' => Hash::make('123456'),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9876543210',
            'otp'   => '123456',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    /** @test */
    public function it_rejects_already_consumed_otp(): void
    {
        OtpRequest::factory()->consumed()->create([
            'phone'    => '9876543210',
            'otp_hash' => Hash::make('123456'),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9876543210',
            'otp'   => '123456',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    /** @test */
    public function it_increments_failed_attempts_on_wrong_otp(): void
    {
        OtpRequest::factory()->create([
            'phone'    => '9876543210',
            'otp_hash' => Hash::make('999999'),
        ]);

        $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9876543210',
            'otp'   => '000000',
        ])->assertStatus(422);

        $this->assertEquals(
            1,
            OtpRequest::where('phone', '9876543210')->value('failed_attempts')
        );
    }

    /** @test */
    public function it_locks_account_after_5_failed_attempts(): void
    {
        OtpRequest::factory()->create([
            'phone'    => '9876543210',
            'otp_hash' => Hash::make('999999'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/verify-otp', [
                'phone' => '9876543210',
                'otp'   => '000000',
            ]);
        }

        $this->assertNotNull(
            OtpRequest::where('phone', '9876543210')->latest('created_at')->value('locked_until')
        );
    }

    // ─── refresh-token ───────────────────────────────────────────────

    /** @test */
    public function it_refreshes_access_token(): void
    {
        OtpRequest::factory()->create([
            'phone'    => '9000000001',
            'otp_hash' => Hash::make('123456'),
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9000000001',
            'otp'   => '123456',
        ])->assertStatus(200);

        $refreshToken = $loginResponse->json('data.refresh_token');

        $response = $this->postJson('/api/v1/auth/refresh-token', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['access_token'], 'message']);
    }

    // ─── logout ──────────────────────────────────────────────────────

    /** @test */
    public function it_logs_out_and_revokes_refresh_token(): void
    {
        OtpRequest::factory()->create([
            'phone'    => '9000000002',
            'otp_hash' => Hash::make('123456'),
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9000000002',
            'otp'   => '123456',
        ])->assertStatus(200);

        $accessToken  = $loginResponse->json('data.access_token');
        $refreshToken = $loginResponse->json('data.refresh_token');

        $response = $this->withHeader('Authorization', "Bearer {$accessToken}")
            ->postJson('/api/v1/auth/logout', ['refresh_token' => $refreshToken]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('refresh_tokens', ['revoked_at' => null]);
    }
}
