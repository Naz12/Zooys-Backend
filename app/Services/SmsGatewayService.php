<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SMS Gateway Service
 * 
 * Unified interface to DYM SMS Gateways Engine for sending and tracking SMS
 * across multiple providers (Twilio, etc.) with HMAC-SHA256 authentication.
 * 
 * Supports: OTP, transactional, marketing, alert, and service messages
 * Used by: Zooys, Akili, Dagu, and future apps
 */
class SmsGatewayService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $keyId;
    protected string $secret;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.sms_gateway.url');
        $this->clientId = config('services.sms_gateway.client_id');
        $this->keyId = config('services.sms_gateway.key_id');
        $this->secret = config('services.sms_gateway.secret');
        $this->timeout = config('services.sms_gateway.timeout', 30);
    }

    /**
     * Send SMS with universal payload
     * 
     * @param string $to Phone number in international format (+251912345678)
     * @param string $body Message content
     * @param string $type Message type: otp|transactional|marketing|alert|service
     * @param array $options Additional options (routing, delivery, metadata, template)
     * @return array Response with status, message_id, provider, segments
     * @throws \Exception
     */
    public function sendSms(string $to, string $body, string $type = 'transactional', array $options = []): array
    {
        // Build universal SMS payload v1
        $payload = [
            'v' => '1',
            'to' => $to,
            'body' => $body,
            'type' => $type,
            'routing' => $options['routing'] ?? ['profile' => 'local'],
            'delivery' => $options['delivery'] ?? ['report_level' => 'basic'],
            'metadata' => array_merge(
                ['app' => $this->clientId],
                $options['metadata'] ?? []
            )
        ];

        // If template provided, use it instead of body
        if (isset($options['template'])) {
            unset($payload['body']);
            $payload['template'] = $options['template'];
        }

        Log::info('SMS Gateway: Sending SMS', [
            'to' => $to,
            'type' => $type,
            'client_id' => $this->clientId
        ]);

        try {
            $response = $this->makeRequest('POST', '/sms/send', $payload);

            Log::info('SMS Gateway: SMS sent successfully', [
                'message_id' => $response['message_id'] ?? null,
                'provider' => $response['provider_selected'] ?? null,
                'status' => $response['status'] ?? null
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('SMS Gateway: Failed to send SMS', [
                'to' => $to,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send OTP code
     * 
     * @param string $to Phone number
     * @param string $code OTP code
     * @param array $options Additional options
     * @return array
     */
    public function sendOtp(string $to, string $code, array $options = []): array
    {
        $body = $options['message'] ?? "Your verification code is {$code}";
        
        $options['metadata'] = array_merge(
            ['campaign_id' => 'otp'],
            $options['metadata'] ?? []
        );

        return $this->sendSms($to, $body, 'otp', $options);
    }

    /**
     * Send transactional message (order confirmation, receipt, etc.)
     * 
     * @param string $to Phone number
     * @param string $message Message content
     * @param array $options Additional options
     * @return array
     */
    public function sendTransactional(string $to, string $message, array $options = []): array
    {
        return $this->sendSms($to, $message, 'transactional', $options);
    }

    /**
     * Send marketing message
     * 
     * @param string $to Phone number
     * @param string $message Message content
     * @param array $options Additional options
     * @return array
     */
    public function sendMarketing(string $to, string $message, array $options = []): array
    {
        return $this->sendSms($to, $message, 'marketing', $options);
    }

    /**
     * Send alert message
     * 
     * @param string $to Phone number
     * @param string $message Message content
     * @param array $options Additional options
     * @return array
     */
    public function sendAlert(string $to, string $message, array $options = []): array
    {
        return $this->sendSms($to, $message, 'alert', $options);
    }

    /**
     * Send service notification
     * 
     * @param string $to Phone number
     * @param string $message Message content
     * @param array $options Additional options
     * @return array
     */
    public function sendService(string $to, string $message, array $options = []): array
    {
        return $this->sendSms($to, $message, 'service', $options);
    }

    /**
     * Get message status
     * 
     * @param string $messageId Message ID from send response
     * @return array Status info
     * @throws \Exception
     */
    public function getMessageStatus(string $messageId): array
    {
        try {
            $response = $this->makeRequest('GET', "/messages/{$messageId}");

            Log::info('SMS Gateway: Retrieved message status', [
                'message_id' => $messageId,
                'status' => $response['status'] ?? null
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('SMS Gateway: Failed to get message status', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if SMS was delivered
     * 
     * @param string $messageId Message ID
     * @return bool
     */
    public function isDelivered(string $messageId): bool
    {
        try {
            $status = $this->getMessageStatus($messageId);
            return isset($status['status']) && $status['status'] === 'delivered';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Poll message status until completion or timeout
     * 
     * @param string $messageId Message ID
     * @param int $maxAttempts Maximum polling attempts
     * @param int $delaySeconds Delay between attempts
     * @return array Final status
     */
    public function pollMessageStatus(string $messageId, int $maxAttempts = 30, int $delaySeconds = 2): array
    {
        $attempts = 0;
        $finalStatuses = ['delivered', 'failed', 'undelivered'];

        while ($attempts < $maxAttempts) {
            $status = $this->getMessageStatus($messageId);
            
            if (isset($status['status']) && in_array($status['status'], $finalStatuses)) {
                return $status;
            }

            sleep($delaySeconds);
            $attempts++;
        }

        throw new \Exception("Message status polling timed out after {$maxAttempts} attempts");
    }

    /**
     * Make authenticated request to SMS Gateway
     * 
     * @param string $method HTTP method
     * @param string $endpoint Endpoint path
     * @param array|null $payload Request payload
     * @return array Response data
     * @throws \Exception
     */
    protected function makeRequest(string $method, string $endpoint, ?array $payload = null): array
    {
        $url = $this->baseUrl . $endpoint;
        $timestamp = time();
        $idempotencyKey = (string) Str::uuid();

        // Prepare raw body for signature
        $rawBody = $payload ? json_encode($payload, JSON_UNESCAPED_SLASHES) : '';
        
        // Generate HMAC-SHA256 signature
        $signature = $this->generateSignature($rawBody);

        // Build headers
        $headers = [
            'Content-Type' => 'application/json',
            'X-Client-Id' => $this->clientId,
            'X-Key-Id' => $this->keyId,
            'X-Timestamp' => (string) $timestamp,
            'Idempotency-Key' => $idempotencyKey,
            'X-Signature' => $signature,
        ];

        Log::debug('SMS Gateway: Making request', [
            'method' => $method,
            'url' => $url,
            'client_id' => $this->clientId,
            'timestamp' => $timestamp,
            'idempotency_key' => $idempotencyKey
        ]);

        // Make request
        $httpClient = Http::timeout($this->timeout)
            ->withHeaders($headers);

        if ($method === 'POST' && $payload) {
            $httpClient = $httpClient->withBody($rawBody, 'application/json');
        }

        $response = $httpClient->send($method, $url);

        // Handle response
        if ($response->failed()) {
            $errorBody = $response->body();
            Log::error('SMS Gateway: Request failed', [
                'status' => $response->status(),
                'body' => $errorBody
            ]);
            throw new \Exception("SMS Gateway request failed: {$errorBody}");
        }

        return $response->json();
    }

    /**
     * Generate HMAC-SHA256 signature
     * 
     * @param string $rawBody Raw request body
     * @return string Signature with sha256= prefix
     */
    protected function generateSignature(string $rawBody): string
    {
        $hash = hash_hmac('sha256', $rawBody, $this->secret);
        return 'sha256=' . $hash;
    }

    /**
     * Validate phone number format
     * 
     * @param string $phone Phone number
     * @return bool
     */
    public function validatePhone(string $phone): bool
    {
        // Basic validation for international format
        return preg_match('/^\+[1-9]\d{1,14}$/', $phone) === 1;
    }

    /**
     * Format phone number to international format
     * 
     * @param string $phone Phone number
     * @param string $defaultCountryCode Default country code (e.g., '251' for Ethiopia)
     * @return string Formatted phone number
     */
    public function formatPhone(string $phone, string $defaultCountryCode = '251'): string
    {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);

        // If already starts with +, return as is
        if (strpos($phone, '+') === 0) {
            return $phone;
        }

        // If starts with 0, replace with country code
        if (strpos($phone, '0') === 0) {
            return '+' . $defaultCountryCode . substr($phone, 1);
        }

        // Otherwise, add + and country code
        return '+' . $defaultCountryCode . $phone;
    }

    /**
     * Get supported message types
     * 
     * @return array
     */
    public function getSupportedTypes(): array
    {
        return ['otp', 'transactional', 'marketing', 'alert', 'service'];
    }

    /**
     * Check SMS Gateway health
     * 
     * @return bool
     */
    public function checkHealth(): bool
    {
        try {
            // Try a simple request to verify connectivity
            $response = Http::timeout(5)->get($this->baseUrl);
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('SMS Gateway: Health check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get service info
     * 
     * @return array
     */
    public function getServiceInfo(): array
    {
        return [
            'service' => 'SMS Gateway',
            'base_url' => $this->baseUrl,
            'client_id' => $this->clientId,
            'timeout' => $this->timeout,
            'supported_types' => $this->getSupportedTypes(),
            'healthy' => $this->checkHealth()
        ];
    }
}















