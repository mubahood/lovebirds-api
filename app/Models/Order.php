<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;

class Order extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user', 'order_state', 'temporary_id', 'amount', 'order_total', 
        'total_amount', 'payment_confirmation', 'description', 'mail', 
        'order_details', 'date_created', 'date_updated', 'delivery_district',
        'customer_name', 'customer_phone_number_1', 'customer_phone_number_2', 
        'customer_address', 'stripe_id', 'stripe_text', 'stripe_url', 
        'stripe_paid', 'stripe_product_id', 'stripe_price_id'
    ];
    
    //boot
    public static function boot()
    {
        parent::boot();
        //created
        self::created(function ($m) {
            try {
                $m->create_payment_link();
            } catch (\Throwable $th) {
                //throw $th;
            }
        });
        self::deleting(function ($m) {
            try {
                $items = OrderedItem::where('order', $m->id)->get();
                foreach ($items as $item) {
                    $item->delete();
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        });
    }

    public function create_payment_link()
    {
        $stripe_key = env('STRIPE_KEY');
        if (($this->stripe_id != null) && (strlen($this->stripe_id) > 0)) {
            return;
        }

        $items = $this->get_items();
        if (count($items) < 1) {
            $this->delete();
            throw new \Exception("No items to create payment link");
            return;
        }

        // Calculate and update total amount
        $this->calculateTotalAmount();
        
        // Get customer information
        $customer = $this->customer();
        $customer_name = 'Guest Customer #' . $this->id;
        
        if ($customer) {
            try {
                // Convert customer to array to safely access fields
                $customer_data = $customer->toArray();
                
                if (!empty($customer_data['first_name']) || !empty($customer_data['last_name'])) {
                    $name_parts = array_filter([
                        $customer_data['first_name'] ?? '',
                        $customer_data['last_name'] ?? ''
                    ]);
                    $customer_name = implode(' ', $name_parts);
                } elseif (!empty($customer_data['name'])) {
                    $customer_name = $customer_data['name'];
                } elseif (!empty($customer_data['email'])) {
                    $customer_name = explode('@', $customer_data['email'])[0]; // Use email username part
                }
            } catch (\Exception $e) {
                // If there's any issue accessing customer data, use fallback
                $customer_name = 'Customer #' . $this->user;
            }
        }
        
        // Calculate total order amount (in cents for Stripe)
        $total_amount = intval(floatval($this->total_amount) * 100);
        
        // Create product name: Order number + customer name
        $product_name = "Order #{$this->id} - {$customer_name}";
        
        $isSuccess = false;
        $resp = "";
        $stripe = new \Stripe\StripeClient($stripe_key);
        
        try {
            // Create a Stripe product for this order
            $product = $stripe->products->create([
                'name' => $product_name,
                'description' => $this->getOrderDescription(),
                'metadata' => [
                    'order_id' => $this->id,
                    'customer_id' => $this->user,
                    'lovebirds_source' => 'order_payment'
                ]
            ]);

            // Create a price for this product
            $price = $stripe->prices->create([
                'unit_amount' => $total_amount,
                'currency' => 'cad',
                'product' => $product->id,
                'metadata' => [
                    'order_id' => $this->id,
                    'order_total' => $this->total_amount
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
                    'order_id' => $this->id,
                    'customer_id' => $this->user,
                    'order_total' => $this->total_amount
                ],
                'after_completion' => [
                    'type' => 'redirect',
                    'redirect' => [
                        'url' => env('APP_URL', 'https://lovebirds.ca') . '/order-success?order_id=' . $this->id
                    ]
                ]
            ]);
            
            $isSuccess = true;
            
        } catch (\Throwable $th) {
            $isSuccess = false;
            $resp = $th->getMessage();
            Log::error('Stripe payment link creation failed: ' . $th->getMessage(), [
                'order_id' => $this->id,
                'customer_id' => $this->user
            ]);
        }

        if ($isSuccess) {
            $this->stripe_id = $resp->id;
            $this->stripe_url = $resp->url;
            $this->stripe_product_id = $product->id ?? null;
            $this->stripe_price_id = $price->id ?? null;
            $this->stripe_paid = 'No';
            $this->save();
        } else {
            throw new \Exception("Failed to create Stripe payment link: " . $resp);
        }
    }

    /**
     * Calculate the total amount for this order
     */
    public function calculateTotalAmount()
    {
        $items = $this->get_items();
        $total = 0;
        
        foreach ($items as $item) {
            $total += floatval($item->amount); // item->amount is already item price * quantity
        }
        
        // Apply tax (13% HST for Canada)
        $tax = $total * 0.13;
        $grand_total = $total + $tax;
        
        $this->total_amount = number_format($grand_total, 2, '.', '');
        $this->order_total = $this->total_amount; // Keep order_total in sync
        $this->save();
        
        return $grand_total;
    }

    /**
     * Generate order description for Stripe product
     */
    private function getOrderDescription()
    {
        $items = $this->get_items();
        $description = "LoveBirds Order containing: ";
        $item_descriptions = [];
        
        foreach ($items as $item) {
            $item_descriptions[] = "{$item->product_name} (Qty: {$item->qty})";
        }
        
        $description .= implode(', ', $item_descriptions);
        
        // Limit description to 500 characters (Stripe limit)
        if (strlen($description) > 500) {
            $description = substr($description, 0, 497) . '...';
        }
        
        return $description;
    }
    public function get_items()
    {
        $items = [];
        foreach (
            OrderedItem::where([
                'order' => $this->id
            ])->get() as $_item
        ) {
            $pro = Product::find($_item->product);
            if ($pro == null) {
                continue;
            }
            
            // Add product information to the item
            $_item->product_name = $pro->name;
            $_item->product_feature_photo = $pro->feature_photo;
            $_item->product_price_1 = $pro->price_1;
            $_item->product_quantity = $_item->qty;
            $_item->product_id = $pro->id;
            $_item->product_image = $pro->feature_photo; // Add this for frontend
            $_item->product_price = $_item->amount; // Use actual order item price
            
            $items[] = $_item;
        }
        return $items;
    }

    //belongs to customer
    public function customer()
    {
        return $this->belongsTo(User::class, 'user');
    }

    //get payment link
    public function payment_link()
    {
        if ($this->stripe_url != null && strlen($this->stripe_url) > 5) {
            return $this->stripe_url;
        }

        $stripe = env('STRIPE_KEY');
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_KEY')
        );

        $name = 'Order payment for ' . date('Y-m-d H:i:s') . " " . rand(1, 100000);

        $resp = null;
        try {
            $resp = $stripe->products->create([
                'name' => $name,
                'default_price_data' => [
                    'currency' => 'cad',
                    'unit_amount' => 1 * 100,
                ],
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
        if ($resp == null) {
            throw new \Exception("Error Processing Request", 1);
        }
        if ($resp->default_price == null) {
            throw new \Exception("Error Processing Request", 1);
        }
        $linkResp = null;
        try {
            $linkResp = $stripe->paymentLinks->create([
                'currency' => 'cad',
                'line_items' => [
                    [
                        'price' => $resp->default_price,
                        'quantity' => 1,
                    ]
                ]
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
        if ($linkResp == null) {
            throw new \Exception("Error Processing Request", 1);
        }
    }

    /**
     * Check and update payment status from Stripe
     */
    public function checkPaymentStatus()
    {
        if (!$this->stripe_id || strlen($this->stripe_id) === 0) {
            return false;
        }

        try {
            $stripe = new \Stripe\StripeClient(env('STRIPE_KEY'));
            
            // Get the payment link from Stripe
            $paymentLink = $stripe->paymentLinks->retrieve($this->stripe_id);
            
            if ($paymentLink) {
                // Check if there are any completed sessions for this payment link
                $sessions = $stripe->checkout->sessions->all([
                    'payment_link' => $this->stripe_id,
                    'status' => 'complete',
                    'limit' => 1,
                ]);

                if (count($sessions->data) > 0) {
                    // Payment found, mark as paid
                    $this->stripe_paid = 1;
                    $this->payment_confirmation = 'PAID';
                    $this->save();
                    
                    Log::info('Order payment status updated to PAID: ' . $this->id);
                    return true;
                }
            }
            
            return false;
        } catch (\Throwable $e) {
            Log::error('Error checking order payment status: ' . $e->getMessage(), [
                'order_id' => $this->id,
                'stripe_id' => $this->stripe_id
            ]);
            return false;
        }
    }

    /**
     * Check if order is paid
     */
    public function isPaid()
    {
        return $this->stripe_paid == 1 || $this->payment_confirmation === 'PAID';
    }
}
