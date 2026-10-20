<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Support\Enums\CustomerType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsCustomer(Customer $customer): string
    {
        return JWTAuth::fromUser($customer);
    }

    // ─── PUT /api/v1/profile ─────────────────────────────────────────

    /** @test */
    public function it_updates_name_and_email(): void
    {
        $customer = Customer::factory()->create(['type' => CustomerType::B2C]);
        $token    = $this->actingAsCustomer($customer);

        $response = $this->putJson('/api/v1/profile', [
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com');

        $this->assertDatabaseHas('customers', [
            'id'   => $customer->id,
            'name' => 'John Doe',
        ]);
    }

    /** @test */
    public function it_upgrades_to_b2b_when_gst_number_provided(): void
    {
        $customer = Customer::factory()->create(['type' => CustomerType::B2C, 'gst_number' => null]);
        $token    = $this->actingAsCustomer($customer);

        $response = $this->putJson('/api/v1/profile', [
            'name'       => 'Acme Corp',
            'gst_number' => '29ABCDE1234F1Z5',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.type', CustomerType::B2B->value);

        $this->assertDatabaseHas('customers', [
            'id'         => $customer->id,
            'type'       => CustomerType::B2B->value,
            'gst_number' => '29ABCDE1234F1Z5',
        ]);
    }

    /** @test */
    public function it_does_not_upgrade_to_b2b_without_gst_number(): void
    {
        $customer = Customer::factory()->create(['type' => CustomerType::B2C, 'gst_number' => null]);
        $token    = $this->actingAsCustomer($customer);

        $this->putJson('/api/v1/profile', [
            'name' => 'Just A Name',
        ], ['Authorization' => "Bearer {$token}"]);

        $this->assertDatabaseHas('customers', [
            'id'   => $customer->id,
            'type' => CustomerType::B2C->value,
        ]);
    }

    /** @test */
    public function it_rejects_invalid_gst_number_format(): void
    {
        $customer = Customer::factory()->create();
        $token    = $this->actingAsCustomer($customer);

        $response = $this->putJson('/api/v1/profile', [
            'gst_number' => 'INVALID-GST',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('data.errors.gst_number', fn ($v) => count($v) > 0);
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->putJson('/api/v1/profile', ['name' => 'Test']);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_allows_partial_updates(): void
    {
        $customer = Customer::factory()->create(['name' => 'Original', 'email' => 'orig@test.com']);
        $token    = $this->actingAsCustomer($customer);

        $response = $this->putJson('/api/v1/profile', [
            'name' => 'Updated',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.email', 'orig@test.com');
    }
}
