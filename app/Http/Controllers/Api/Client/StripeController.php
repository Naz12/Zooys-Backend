<?php

namespace App\Http\Controllers\Api\Client;

use Stripe\Webhook;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\PaymentHistory;
use App\Services\SubscriptionManagementService;
use App\Services\UsageAlertService;
use Illuminate\Http\Request;
use App\Mail\PaymentFailedMail;
use App\Mail\GracePeriodMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create Stripe checkout session
     */
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);

        if (!$plan->is_active) {
            return response()->json(['error' => 'Plan is not available'], 400);
        }

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $plan->currency,
                        'product_data' => [
                            'name' => $plan->name . ' Plan',
                        ],
                        'unit_amount' => $plan->price * 100, // Convert to cents
                        'recurring' => [
                            'interval' => 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => config('app.frontend_url') . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url') . '/subscription/cancel',
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                ],
                'customer_email' => $user->email,
            ]);

            return response()->json([
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe checkout session creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create checkout session'], 500);
        }
    }

    /**
     * Verify checkout session completion
     */
    public function verifyCheckoutSession(Request $request, $sessionId)
    {
        try {
            $session = Session::retrieve($sessionId);
            
            if ($session->payment_status === 'paid') {
                // Get user from metadata
                $userId = $session->metadata->user_id ?? null;
                $planId = $session->metadata->plan_id ?? null;
                
                if ($userId && $planId) {
                    $user = \App\Models\User::find($userId);
                    $plan = Plan::find($planId);
                    
                    if ($user && $plan) {
                        // Check if subscription was already created by webhook
                        $subscription = Subscription::where('user_id', $userId)
                            ->where('plan_id', $planId)
                            ->where('active', true)
                            ->first();
                        
                        if ($subscription) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Payment verified successfully',
                                'subscription' => [
                                    'id' => $subscription->id,
                                    'plan_name' => $plan->name,
                                    'plan_price' => $plan->price,
                                    'starts_at' => $subscription->starts_at,
                                    'ends_at' => $subscription->ends_at,
                                ],
                                'session' => [
                                    'id' => $session->id,
                                    'payment_status' => $session->payment_status,
                                    'amount_total' => $session->amount_total,
                                ]
                            ]);
                        } else {
                            // Subscription not created yet, webhook might be delayed
                            return response()->json([
                                'status' => 'pending',
                                'message' => 'Payment verified but subscription processing',
                                'session' => [
                                    'id' => $session->id,
                                    'payment_status' => $session->payment_status,
                                    'amount_total' => $session->amount_total,
                                ]
                            ], 202);
                        }
                    }
                }
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment verified successfully',
                    'session' => [
                        'id' => $session->id,
                        'payment_status' => $session->payment_status,
                        'amount_total' => $session->amount_total,
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment not completed',
                    'session' => [
                        'id' => $session->id,
                        'payment_status' => $session->payment_status,
                    ]
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Stripe checkout verification failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify checkout session'
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        if (app()->environment('local')) {
            $event = json_decode($payload);
        } else {
            $endpointSecret = config('services.stripe.webhook_secret');
            $sigHeader = $request->server('HTTP_STRIPE_SIGNATURE');

            try {
                $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } catch (\Exception $e) {
                Log::error('Stripe webhook error: ' . $e->getMessage());
                return response()->json(['error' => 'Invalid payload'], 400);
            }
        }

        // Debug log
        Log::info('🔔 Stripe Webhook Received', [
            'type' => $event->type ?? 'unknown',
            'object' => $event->data->object ?? null,
        ]);

        // Normalize object
        $object = (object) ($event->data->object->stdClass ?? $event->data->object);

        $eventType = $event->type ?? 'unknown';
        
        switch ($eventType) {
            case 'checkout.session.completed':
                $userId = $object->metadata->user_id ?? null;
                $planId = $object->metadata->plan_id ?? null;

                if ($userId && $planId) {
                    $subscriptionService = app(SubscriptionManagementService::class);
                    $subscriptionService->createSubscription(
                        \App\Models\User::find($userId),
                        $planId,
                        [
                            'stripe_id' => $object->subscription ?? $object->id,
                            'stripe_customer_id' => $object->customer ?? null,
                            'stripe_payment_id' => $object->payment_intent ?? null,
                        ]
                    );
                }
                break;

            case 'customer.subscription.updated':
                $stripeId = $object->id ?? null;
                if ($stripeId) {
                    $subscription = Subscription::where('stripe_id', $stripeId)->first();
                    if ($subscription) {
                        // Handle subscription updates (plan changes, etc.)
                        Log::info("Subscription updated via Stripe", [
                            'subscription_id' => $subscription->id,
                            'stripe_subscription_id' => $stripeId,
                        ]);
                    }
                }
                break;

            case 'invoice.payment_succeeded':
                $customerId = $object->customer ?? null;
                if ($customerId) {
                    $subscription = Subscription::where('stripe_customer_id', $customerId)->first();
                    if ($subscription) {
                        // Extend subscription period
                        $subscription->update([
                            'ends_at' => now()->addMonth(),
                            'usage_reset_date' => now()->addMonth(),
                        ]);

                        // Remove grace period if active
                        $subscriptionService = app(SubscriptionManagementService::class);
                        $subscriptionService->removeGracePeriod($subscription);

                        // Record successful payment
                        PaymentHistory::create([
                            'user_id' => $subscription->user_id,
                            'subscription_id' => $subscription->id,
                            'amount' => $object->amount_paid / 100, // Convert from cents
                            'currency' => $object->currency,
                            'status' => 'completed',
                            'payment_type' => 'subscription',
                            'stripe_payment_id' => $object->id,
                            'metadata' => [
                                'invoice_id' => $object->id,
                                'billing_reason' => $object->billing_reason ?? 'subscription_cycle',
                            ],
                        ]);

                        Log::info("Payment succeeded for subscription {$subscription->id}");
                    }
                }
                break;

            case 'invoice.payment_failed':
                $customerId = $object->customer ?? null;

                if ($customerId) {
                    $subscription = Subscription::where('stripe_customer_id', $customerId)->first();

                    if ($subscription) {
                        $user = $subscription->user;

                        if ($user) {
                            // Apply grace period
                            $subscriptionService = app(SubscriptionManagementService::class);
                            $subscriptionService->applyGracePeriod($subscription);

                            // Send grace period email
                            Mail::to($user->email)->send(new GracePeriodMail($user, $subscription));

                            // Record failed payment
                            PaymentHistory::create([
                                'user_id' => $user->id,
                                'subscription_id' => $subscription->id,
                                'amount' => $object->amount_due / 100, // Convert from cents
                                'currency' => $object->currency,
                                'status' => 'failed',
                                'payment_type' => 'subscription',
                                'stripe_payment_id' => $object->id,
                                'metadata' => [
                                    'invoice_id' => $object->id,
                                    'failure_reason' => $object->last_payment_error->message ?? 'Payment failed',
                                ],
                            ]);

                            Log::warning("⚠️ Payment failed for user {$user->email}, subscription #{$subscription->id}, grace period applied");
                        }
                    }
                }
                break;

            case 'customer.subscription.deleted':
                $stripeId = $object->id ?? null;
                if ($stripeId) {
                    Subscription::where('stripe_id', $stripeId)
                        ->update(['active' => false]);
                }
                break;
        }

        return response()->json(['status' => 'success']);
    }
}