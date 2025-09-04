<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AdminUser;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class SubscriptionSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = AdminUser::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone_number' => '+1234567890',
            'password' => Hash::make('password'),
            'email_verified' => 'yes',
            'phone_verified' => 'yes',
        ]);
        
        // Generate auth token
        $this->token = auth('api')->login($this->user);
    }

    /** @test */
    public function it_can_get_subscription_plans()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/subscription-plans');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertTrue($data['status'] === 1);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        
        // Check that we have at least the basic plans
        $this->assertGreaterThan(0, count($data['data']));
    }

    /** @test */
    public function it_can_get_empty_subscription_history()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/subscription-history');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertTrue($data['status'] === 1);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertCount(0, $data['data']);
    }

    /** @test */
    public function it_can_create_subscription()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post('/api/create-subscription', [
            'plan_type' => 'monthly',
            'amount' => 30.00,
            'currency' => 'cad'
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertTrue($data['status'] === 1);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('subscription_id', $data['data']);
        $this->assertArrayHasKey('payment_link', $data['data']);
    }

    /** @test */
    public function it_validates_required_fields_for_subscription_creation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post('/api/create-subscription', [
            // Missing required fields
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertTrue($data['status'] === 0);
        $this->assertStringContainsString('required', strtolower($data['message']));
    }

    /** @test */
    public function it_can_get_subscription_history_with_data()
    {
        // Create a subscription first
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_type' => 'monthly',
            'amount' => 30.00,
            'currency' => 'cad',
            'status' => 'active',
            'payment_status' => 'paid',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/subscription-history');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertTrue($data['status'] === 1);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);
        $this->assertEquals('monthly', $data['data'][0]['plan_type']);
        $this->assertEquals(30.00, $data['data'][0]['amount']);
    }

    /** @test */
    public function it_can_refresh_subscription_payment_status()
    {
        // Create a subscription with pending payment
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_type' => 'monthly',
            'amount' => 30.00,
            'currency' => 'cad',
            'status' => 'pending',
            'payment_status' => 'pending',
            'stripe_subscription_id' => 'test_sub_123',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post('/api/refresh-subscription-payment', [
            'subscription_id' => $subscription->id
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        // The response should be successful even if payment is not found
        // (since we're using test Stripe data)
        $this->assertTrue($data['status'] === 1);
        $this->assertArrayHasKey('data', $data);
    }

    /** @test */
    public function it_requires_authentication_for_protected_routes()
    {
        $routes = [
            ['GET', '/api/subscription-history'],
            ['GET', '/api/subscription-plans'],
            ['POST', '/api/create-subscription'],
            ['POST', '/api/refresh-subscription-payment'],
            ['POST', '/api/cancel-subscription'],
        ];

        foreach ($routes as $route) {
            $response = $this->{strtolower($route[0])}($route[1]);
            
            // Should return unauthorized or redirect
            $this->assertTrue(
                $response->status() === 401 || $response->status() === 302,
                "Route {$route[1]} should require authentication"
            );
        }
    }
}
