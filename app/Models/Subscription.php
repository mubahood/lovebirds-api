<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'plan_name',
        'plan_duration',
        'amount',
        'currency',
        'status',
        'stripe_subscription_id',
        'stripe_customer_id',
        'stripe_payment_intent_id',
        'stripe_product_id',
        'stripe_price_id',
        'stripe_url',
        'stripe_paid',
        'payment_method',
        'start_date',
        'end_date',
        'trial_end_date',
        'cancelled_at',
        'payment_confirmation',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'trial_end_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive()
    {
        return $this->status === 'active' && 
               $this->end_date && 
               $this->end_date->isFuture();
    }

    /**
     * Check if subscription is paid
     */
    public function isPaid()
    {
        return $this->stripe_paid === 'Yes' || $this->payment_confirmation === 'PAID';
    }

    /**
     * Create Stripe payment link for subscription
     */
    public function create_payment_link()
    {
        $stripe_key = env('STRIPE_KEY');
        if (($this->stripe_subscription_id != null) && (strlen($this->stripe_subscription_id) > 0)) {
            return;
        }

        try {
            $stripe = new \Stripe\StripeClient($stripe_key);
            
            // Get user information
            $user = $this->user;
            $customer_name = 'Guest Customer #' . $this->id;
            
            if ($user) {
                $customer_name = trim($user->first_name . ' ' . $user->last_name);
                if (empty($customer_name)) {
                    $customer_name = $user->email ?? 'User #' . $user->id;
                }
            }

            // Define subscription plans with prices in cents (CAD)
            $plan_prices = [
                'weekly' => 1000,    // $10.00 CAD
                'monthly' => 3000,   // $30.00 CAD
                'quarterly' => 7000, // $70.00 CAD
            ];

            $amount_cents = $plan_prices[$this->plan_id] ?? 3000;
            $product_name = ucfirst($this->plan_id) . ' Premium Subscription';

            // Create a Stripe product for this subscription
            $product = $stripe->products->create([
                'name' => $product_name,
                'description' => $this->getSubscriptionDescription(),
                'metadata' => [
                    'subscription_id' => $this->id,
                    'user_id' => $this->user_id,
                    'plan_id' => $this->plan_id,
                    'lovebirds_source' => 'subscription_payment'
                ]
            ]);

            // Create a price for this product
            $price = $stripe->prices->create([
                'unit_amount' => $amount_cents,
                'currency' => 'cad',
                'product' => $product->id,
                'metadata' => [
                    'subscription_id' => $this->id,
                    'plan_id' => $this->plan_id,
                    'user_id' => $this->user_id
                ]
            ]);

            // Create payment link using the dynamic price
            $resp = $stripe->paymentLinks->create([
                'currency' => 'cad',
                'line_items' => [
                    [
                        'price' => $price->id,
                        'quantity' => 1,
                    ]
                ],
                'metadata' => [
                    'subscription_id' => $this->id,
                    'user_id' => $this->user_id,
                    'plan_id' => $this->plan_id
                ],
                'after_completion' => [
                    'type' => 'redirect',
                    'redirect' => [
                        'url' => env('APP_URL', 'https://lovebirds.ca') . '/subscription-success?subscription_id=' . $this->id
                    ]
                ]
            ]);
            
            $isSuccess = true;
            
        } catch (\Throwable $th) {
            $isSuccess = false;
            $resp = $th->getMessage();
            Log::error('Stripe subscription payment link creation failed: ' . $th->getMessage(), [
                'subscription_id' => $this->id,
                'user_id' => $this->user_id,
                'plan_id' => $this->plan_id
            ]);
        }

        if ($isSuccess) {
            $this->stripe_subscription_id = $resp->id;
            $this->stripe_url = $resp->url;
            $this->stripe_product_id = $product->id ?? null;
            $this->stripe_price_id = $price->id ?? null;
            $this->amount = $amount_cents / 100; // Store in dollars
            $this->save();

            Log::info('Subscription payment link created successfully', [
                'subscription_id' => $this->id,
                'stripe_url' => $this->stripe_url
            ]);
        }
    }

    /**
     * Get subscription description for Stripe
     */
    private function getSubscriptionDescription()
    {
        $features = [
            'weekly' => 'Unlimited swipes, 5 super likes/day, see who likes you, profile boost',
            'monthly' => 'All weekly features + unlimited rewinds, read receipts, priority support, advanced filters',
            'quarterly' => 'All monthly features + profile insights, date planning tools, relationship coaching, VIP service'
        ];

        return $features[$this->plan_id] ?? 'Premium subscription features';
    }

    /**
     * Calculate total amount including taxes
     */
    public function calculateTotalAmount()
    {
        $subtotal = $this->amount;
        $tax_rate = 0.13; // 13% HST for Canada
        $tax_amount = $subtotal * $tax_rate;
        $total_amount = $subtotal + $tax_amount;

        $this->update([
            'amount' => $total_amount
        ]);

        return $total_amount;
    }

    /**
     * Activate subscription after successful payment
     */
    public function activate()
    {
        $duration_map = [
            'weekly' => '+1 week',
            'monthly' => '+1 month',
            'quarterly' => '+3 months'
        ];

        $duration = $duration_map[$this->plan_id] ?? '+1 month';
        
        $this->update([
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->modify($duration),
            'stripe_paid' => 'Yes',
            'payment_confirmation' => 'PAID'
        ]);

        // Update user's subscription status
        $this->user->update([
            'subscription_status' => 'active',
            'subscription_plan' => $this->plan_id,
            'subscription_started_at' => now(),
            'subscription_expires_at' => $this->end_date
        ]);

        Log::info('Subscription activated', [
            'subscription_id' => $this->id,
            'user_id' => $this->user_id,
            'plan_id' => $this->plan_id,
            'end_date' => $this->end_date
        ]);
    }

    /**
     * Check payment status with Stripe
     */
    public function checkPaymentStatus()
    {
        if (!$this->stripe_subscription_id) {
            return ['status' => 'unpaid', 'message' => 'No Stripe payment link found'];
        }

        try {
            $stripe_key = env('STRIPE_KEY');
            $stripe = new \Stripe\StripeClient($stripe_key);

            // Get payment link status
            $payment_link = $stripe->paymentLinks->retrieve($this->stripe_subscription_id);
            
            if ($payment_link && $payment_link->active === false) {
                // Payment link has been used, likely paid
                $this->update([
                    'stripe_paid' => 'Yes',
                    'payment_confirmation' => 'PAID'
                ]);
                
                // Activate subscription if not already active
                if ($this->status !== 'active') {
                    $this->activate();
                }

                return ['status' => 'paid', 'message' => 'Subscription payment confirmed'];
            }

            return ['status' => 'pending', 'message' => 'Payment still pending'];

        } catch (\Throwable $th) {
            Log::error('Error checking subscription payment status: ' . $th->getMessage(), [
                'subscription_id' => $this->id
            ]);
            return ['status' => 'error', 'message' => 'Could not verify payment status'];
        }
    }

    /**
     * Get subscription status display text
     */
    public function getStatusText()
    {
        switch ($this->status) {
            case 'active':
                return $this->isActive() ? 'Active' : 'Expired';
            case 'cancelled':
                return 'Cancelled';
            case 'pending':
                return 'Pending Payment';
            default:
                return ucfirst($this->status);
        }
    }

    /**
     * Get days remaining in subscription
     */
    public function getDaysRemaining()
    {
        if (!$this->end_date) return 0;
        
        $days = $this->end_date->diffInDays(now(), false);
        return max(0, $days);
    }
}
