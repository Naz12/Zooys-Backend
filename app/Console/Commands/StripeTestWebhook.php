<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class StripeTestWebhook extends Command
{
    protected $signature = 'stripe:test-webhook {event=checkout.session.completed}';
    protected $description = 'Send a fake Stripe webhook event to your local app for testing';

    public function handle(): int
    {
        $eventType = $this->argument('event');

        switch ($eventType) {
            case 'checkout.session.completed':
                $payload = [
                    'id' => 'evt_test_' . uniqid(),
                    'object' => 'event',
                    'type' => 'checkout.session.completed',
                    'data' => [
                        'object' => [
                            'id' => 'cs_test_' . uniqid(),
                            'object' => 'checkout.session',
                            'subscription' => 'sub_test_' . uniqid(),
                            'customer' => 'cus_test_' . uniqid(),
                            'metadata' => [
                                'user_id' => 1,
                                'plan_id' => 2,
                            ],
                        ],
                    ],
                ];
                break;

            case 'invoice.payment_failed':
                $payload = [
                    'id' => 'evt_test_' . uniqid(),
                    'object' => 'event',
                    'type' => 'invoice.payment_failed',
                    'data' => [
                        'object' => [
                            'id' => 'in_test_' . uniqid(),
                            'object' => 'invoice',
                            'subscription' => 'sub_test_fake',
                            'customer' => 'cus_test_fake',
                        ],
                    ],
                ];
                break;

            case 'customer.subscription.deleted':
                $payload = [
                    'id' => 'evt_test_' . uniqid(),
                    'object' => 'event',
                    'type' => 'customer.subscription.deleted',
                    'data' => [
                        'object' => [
                            'id' => 'sub_test_' . uniqid(),
                            'object' => 'subscription',
                            'customer' => 'cus_test_fake',
                        ],
                    ],
                ];
                break;

            default:
                $this->error("Unsupported event type: {$eventType}");
                return self::FAILURE;
        }

        $url = config('app.url') . '/api/stripe/webhook';

        $this->info("Sending fake Stripe event [$eventType] to $url ...");

        $response = Http::withoutVerifying()->post($url, $payload);

        $this->info('Response: ' . $response->body());

        return self::SUCCESS;
    }
}