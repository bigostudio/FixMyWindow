<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\ProjectTimeline;
use App\Models\Service;
use App\Models\User;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\Role;
use App\Support\Enums\TimelineActorType;
use App\Support\Enums\TimelineStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(User $user): string
    {
        return JWTAuth::customClaims(['guard' => 'admin'])->fromUser($user);
    }

    // ─── auth guard ──────────────────────────────────────────────────

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(401)->assertJson(['success' => false]);
    }

    // ─── empty state ─────────────────────────────────────────────────

    /** @test */
    public function it_returns_empty_state_when_there_is_no_data(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $token = $this->actingAsAdmin($admin);

        $response = $this->getJson('/api/v1/admin/dashboard', ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_requests'  => ['today' => 0, 'week' => 0, 'month' => 0],
                    'pending'         => 0,
                    'in_progress'     => 0,
                    'completed'       => 0,
                    'cancelled'       => 0,
                    'revenue'         => [
                        'today' => 0, 'week' => 0, 'month' => 0,
                        'today_trend_pct' => 0, 'week_trend_pct' => 0, 'month_trend_pct' => 0,
                    ],
                    'recent_activity' => [],
                    'team_overview'   => [
                        'operations_managers' => 0, 'supervisors' => 0, 'technicians' => 0, 'customers' => 0,
                    ],
                    'urgent_requests' => [],
                ],
            ])
            ->assertJsonCount(7, 'data.weekly_revenue');
    }

    // ─── populated day ───────────────────────────────────────────────

    /** @test */
    public function it_returns_correct_figures_for_a_populated_day(): void
    {
        $now = Carbon::now();

        User::factory()->count(2)->create(['role' => Role::OperationsManager]);
        User::factory()->count(3)->create(['role' => Role::Supervisor]);
        User::factory()->count(4)->create(['role' => Role::Technician]);
        User::factory()->create(['role' => Role::Supervisor, 'is_active' => false]);
        Customer::factory()->count(3)->create();

        $customer = Customer::factory()->create(['name' => 'Vikram Singh']);
        $service  = Service::factory()->create(['name' => 'Emergency Repair']);

        $makeEnquiry = fn (array $overrides) => Enquiry::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'service_id'  => $service->id,
        ], $overrides));

        // today, paid ₹500, pending bucket, too recent to be urgent
        $eq1 = $makeEnquiry([
            'status' => ProjectStatus::New, 'created_at' => $now->copy(),
            'payment_status' => PaymentStatus::Paid, 'payment_amount' => 500, 'paid_at' => $now->copy(),
        ]);

        // 2 days ago, paid ₹1000, in_progress bucket, within week/prev-week? within week
        $makeEnquiry([
            'status' => ProjectStatus::SurveyCompleted, 'created_at' => $now->copy()->subDays(2),
            'payment_status' => PaymentStatus::Paid, 'payment_amount' => 1000, 'paid_at' => $now->copy()->subDays(2),
        ]);

        // 10 days ago, paid ₹2000, completed bucket, within month + within prev-week window
        $makeEnquiry([
            'status' => ProjectStatus::Handovered, 'created_at' => $now->copy()->subDays(10),
            'payment_status' => PaymentStatus::Paid, 'payment_amount' => 2000, 'paid_at' => $now->copy()->subDays(10),
        ]);

        // 40 days ago, unpaid, cancelled bucket, outside month window entirely
        $makeEnquiry([
            'status' => ProjectStatus::Cancelled, 'created_at' => $now->copy()->subDays(40),
            'payment_status' => PaymentStatus::Pending, 'payment_amount' => null, 'paid_at' => null,
        ]);

        // draft today — must be excluded from every count
        $makeEnquiry([
            'status' => ProjectStatus::Draft, 'created_at' => $now->copy(),
            'payment_status' => PaymentStatus::Pending, 'payment_amount' => null, 'paid_at' => null,
        ]);

        // 30h old, status=new -> urgent priority "high"
        $eqHigh = $makeEnquiry([
            'status' => ProjectStatus::New, 'created_at' => $now->copy()->subHours(30),
            'payment_status' => PaymentStatus::Pending, 'payment_amount' => null, 'paid_at' => null,
        ]);

        // 80h old, status=survey_initiated -> urgent priority "urgent"
        $eqUrgent = $makeEnquiry([
            'status' => ProjectStatus::SurveyInitiated, 'created_at' => $now->copy()->subHours(80),
            'payment_status' => PaymentStatus::Pending, 'payment_amount' => null, 'paid_at' => null,
        ]);

        // 30h old but wrong status -> must NOT be urgent
        $makeEnquiry([
            'status' => ProjectStatus::SurveyCompleted, 'created_at' => $now->copy()->subHours(30),
            'payment_status' => PaymentStatus::Pending, 'payment_amount' => null, 'paid_at' => null,
        ]);

        // 10h old, status=new but too recent -> must NOT be urgent
        $makeEnquiry([
            'status' => ProjectStatus::New, 'created_at' => $now->copy()->subHours(10),
            'payment_status' => PaymentStatus::Pending, 'payment_amount' => null, 'paid_at' => null,
        ]);

        ProjectTimeline::factory()->create([
            'enquiry_id' => $eq1->id,
            'status'     => TimelineStatus::New->value,
            'description' => 'Booking created by customer.',
            'actor_type' => TimelineActorType::Customer,
            'created_at' => $now->copy(),
        ]);

        $admin = User::factory()->superAdmin()->create();
        $token = $this->actingAsAdmin($admin);

        $response = $this->getJson('/api/v1/admin/dashboard', ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $data = $response->json('data');

        $this->assertSame(1, $data['total_requests']['today']);
        $this->assertSame(6, $data['total_requests']['week']);
        $this->assertSame(7, $data['total_requests']['month']);

        $this->assertSame(3, $data['pending']);
        $this->assertSame(3, $data['in_progress']);
        $this->assertSame(1, $data['completed']);
        $this->assertSame(1, $data['cancelled']);

        $this->assertSame(500, $data['revenue']['today']);
        $this->assertSame(1500, $data['revenue']['week']);
        $this->assertSame(3500, $data['revenue']['month']);
        $this->assertSame(100, $data['revenue']['today_trend_pct']);
        $this->assertSame(-25, $data['revenue']['week_trend_pct']);
        $this->assertSame(100, $data['revenue']['month_trend_pct']);

        $this->assertCount(7, $data['weekly_revenue']);
        $this->assertSame(1500, array_sum(array_column($data['weekly_revenue'], 'amount')));
        $this->assertSame(500, end($data['weekly_revenue'])['amount']);

        $this->assertSame([
            'operations_managers' => 2,
            'supervisors'         => 3,
            'technicians'         => 4,
            'customers'           => 4,
        ], $data['team_overview']);

        $urgentIds = array_column($data['urgent_requests'], 'id');
        $this->assertContains($eqHigh->enquiry_number, $urgentIds);
        $this->assertContains($eqUrgent->enquiry_number, $urgentIds);
        $this->assertCount(2, $data['urgent_requests']);

        $priorityById = array_column($data['urgent_requests'], 'priority', 'id');
        $this->assertSame('high', $priorityById[$eqHigh->enquiry_number]);
        $this->assertSame('urgent', $priorityById[$eqUrgent->enquiry_number]);

        $createdActivity = collect($data['recent_activity'])->firstWhere('type', 'request_created');
        $this->assertNotNull($createdActivity);
        $this->assertSame('New request from Vikram Singh for Emergency Repair', $createdActivity['message']);
        $this->assertSame($eq1->enquiry_number, $createdActivity['request_id']);
    }

    /** @test */
    public function it_respects_activity_and_urgent_limit_query_params(): void
    {
        $customer = Customer::factory()->create();
        $service  = Service::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $enquiry = Enquiry::factory()->create([
                'customer_id' => $customer->id,
                'service_id'  => $service->id,
                'status'      => ProjectStatus::New,
                'created_at'  => Carbon::now()->subHours(30 + $i),
            ]);

            ProjectTimeline::factory()->create([
                'enquiry_id' => $enquiry->id,
                'created_at' => Carbon::now()->subMinutes($i),
            ]);
        }

        $admin = User::factory()->superAdmin()->create();
        $token = $this->actingAsAdmin($admin);

        $response = $this->getJson(
            '/api/v1/admin/dashboard?activity_limit=2&urgent_limit=1',
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.recent_activity'));
        $this->assertCount(1, $response->json('data.urgent_requests'));
    }

    /** @test */
    public function it_rejects_out_of_range_limit_params(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $token = $this->actingAsAdmin($admin);

        $response = $this->getJson(
            '/api/v1/admin/dashboard?activity_limit=51',
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertStatus(422)->assertJson(['success' => false]);
    }
}
