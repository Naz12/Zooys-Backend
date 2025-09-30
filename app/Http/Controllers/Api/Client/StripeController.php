<?php

namespace App\Http\Controllers\Api\Client;

use Stripe\Webhook;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Mail\PaymentFailedMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class StripeController extends Controller
{
    public function webhook(Request $request)
    {
        $payload = $request->getContent();

        // If running locally, skip Stripe signature validation
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
            'type' => $event->type,
            'object' => $event->data->object,
        ]);

        // Normalize object
        $object = (object) ($event->data->object->stdClass ?? $event->data->object);

        switch ($event->type) {
            case 'checkout.session.completed':
                $userId = $object->metadata->user_id ?? null;
                $planId = $object->metadata->plan_id ?? null;

                if ($userId && $planId) {
                    Subscription::create([
                        'user_id'            => $userId,
                        'plan_id'            => $planId,
                        'stripe_id'          => $object->subscription ?? $object->id,
                        'stripe_customer_id' => $object->customer ?? null,
                        'active'             => true,
                        'starts_at'          => now(),
                        'ends_at'            => now()->addMonth(),
                    ]);
                }
                break;

            case 'invoice.payment_failed':
                $object = (object) ($event->data->object->stdClass ?? $event->data->object);
                $customerId = $object->customer ?? null;

                if ($customerId) {
                    $subscription = Subscription::where('stripe_customer_id', $customerId)->first();

                    if ($subscription) {
                        $user = $subscription->user;

                        if ($user) {
                            // ✅ Send email
                            Mail::to($user->email)->send(new PaymentFailedMail($user));

                            // ✅ Log warning (instead of Filament notification)
                            Log::warning("⚠️ Payment failed for user {$user->email}, subscription #{$subscription->id}");

                            // Mark subscription inactive
                            $subscription->update(['active' => false]);
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