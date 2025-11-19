<?php

namespace App\Console\Commands;

use App\Services\DocumentIntelligenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestDocumentIntelligenceIngest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:doc-ingest 
                            {--text= : Text content to ingest (default: test content)}
                            {--filename=summary.txt : Filename to use}
                            {--lang=eng : Language code}
                            {--model=deepseek-chat : LLM model to use}
                            {--endpoint=/v1/ingest/text : Endpoint path to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Document Intelligence text ingestion endpoint directly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Document Intelligence /v1/ingest/text endpoint...');
        $this->newLine();

        // Get configuration
        $baseUrl = config('services.document_intelligence.url', 'https://doc.akmicroservice.com');
        $tenantId = config('services.document_intelligence.tenant', 'dagu');
        $clientId = config('services.document_intelligence.client_id', 'dev');
        $keyId = config('services.document_intelligence.key_id', 'local');
        $secret = config('services.document_intelligence.secret', 'change_me');

        $this->info('Configuration:');
        $this->line("  Base URL: {$baseUrl}");
        $this->line("  Tenant ID: {$tenantId}");
        $this->line("  Client ID: {$clientId}");
        $this->line("  Key ID: {$keyId}");
        $this->line("  Secret: " . ($secret !== 'change_me' ? str_repeat('*', strlen($secret)) : 'NOT SET (using default)'));
        $this->newLine();

        // Step 1: Test health endpoint first
        $this->info('Step 1: Testing service health...');
        try {
            $docService = app(DocumentIntelligenceService::class);
            $health = $docService->healthCheck();
            
            if ($health['ok'] ?? false) {
                $this->info('✅ Service is healthy!');
                $this->line("  Uptime: " . ($health['uptime'] ?? 0) . " seconds");
                $this->line("  Vector Status: " . ($health['vector_status'] ?? 'unknown'));
                $this->line("  Cache Status: " . ($health['cache_status'] ?? 'unknown'));
            } else {
                $this->warn('⚠️  Service health check failed');
                $this->line("  Error: " . ($health['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('❌ Health check failed: ' . $e->getMessage());
            $this->warn('  Service may not be accessible. Continuing with ingest test...');
        }
        $this->newLine();

        // Get test text
        $testText = $this->option('text') ?? 'This is a test ingestion from YouTube transcript. Summarized PDF text or any prepared content goes here.';
        $filename = $this->option('filename');
        $lang = $this->option('lang');
        $model = $this->option('model');

        $this->info('Step 2: Testing text ingestion...');
        $this->info('Request Details:');
        $this->line("  Text Length: " . strlen($testText) . " characters");
        $this->line("  Filename: {$filename}");
        $this->line("  Language: {$lang}");
        $this->line("  Model: {$model}");
        $this->newLine();

        // Test alternative endpoint path
        $endpointPath = $this->option('endpoint');
        $this->info("Testing endpoint: {$endpointPath}");
        $this->newLine();

        // Test using the service with custom endpoint
        try {
            // If custom endpoint is provided, test it directly via HTTP
            if ($endpointPath !== '/v1/ingest/text') {
                $this->info("Testing custom endpoint path: {$endpointPath}");
                $result = $this->testCustomEndpoint($baseUrl, $endpointPath, $testText, $filename, $lang, $model, $tenantId, $clientId, $keyId, $secret);
            } else {
                $this->info('Calling DocumentIntelligenceService::ingestText()...');
                $result = $docService->ingestText($testText, [
                    'filename' => $filename,
                    'lang' => $lang,
                    'llm_model' => $model,
                    'force_fallback' => true,
                    'metadata' => [
                        'tags' => ['test', 'manual'],
                        'source' => 'test_command',
                        'date' => date('Y-m-d')
                    ]
                ]);
            }

            $this->newLine();
            $this->info('✅ SUCCESS!');
            $this->newLine();
            $this->info('Response:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            
            if (isset($result['doc_id'])) {
                $this->newLine();
                $this->info("Document ID: {$result['doc_id']}");
            }
            
            if (isset($result['job_id'])) {
                $this->info("Job ID: {$result['job_id']}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ FAILED!');
            $this->newLine();
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            
            // Show detailed error info
            $this->warn('Error Details:');
            $this->line("  Exception Type: " . get_class($e));
            $this->line("  File: " . $e->getFile());
            $this->line("  Line: " . $e->getLine());
            
            // Check if it's a 404 error
            if (strpos($e->getMessage(), '404') !== false || strpos($e->getMessage(), 'not found') !== false) {
                $this->newLine();
                $this->warn('⚠️  This appears to be a 404 error. Possible causes:');
                $this->line('  1. The endpoint /v1/ingest/text does not exist on the service');
                $this->line('  2. The service URL is incorrect');
                $this->line('  3. The service is not running');
                $this->line('  4. The endpoint path is different (e.g., /api/v1/ingest/text)');
                $this->newLine();
                $this->info('💡 Troubleshooting:');
                $this->line('  - Verify the endpoint exists: curl -X GET ' . $baseUrl . '/health');
                $this->line('  - Check service documentation for correct endpoint path');
                $this->line('  - Ensure DOC_INTELLIGENCE_SECRET is set in .env file');
            }

            return Command::FAILURE;
        }
    }

    /**
     * Test custom endpoint path directly via HTTP
     */
    private function testCustomEndpoint($baseUrl, $endpointPath, $text, $filename, $lang, $model, $tenantId, $clientId, $keyId, $secret)
    {
        $method = 'POST';
        $timestamp = time();
        
        // Generate signature
        $baseString = implode('|', [$method, $endpointPath, '', $timestamp, $clientId, $keyId]);
        $signature = hash_hmac('sha256', $baseString, $secret);
        
        $headers = [
            'Content-Type' => 'application/json',
            'X-Tenant-Id' => $tenantId,
            'X-Client-Id' => $clientId,
            'X-Key-Id' => $keyId,
            'X-Timestamp' => (string)$timestamp,
            'X-Signature' => $signature,
        ];
        
        $payload = [
            'text' => $text,
            'filename' => $filename,
            'lang' => $lang,
            'force_fallback' => true,
            'llm_model' => $model,
            'metadata' => [
                'tags' => ['test', 'manual'],
                'source' => 'test_command',
                'date' => date('Y-m-d')
            ]
        ];
        
        $this->line("  Full URL: {$baseUrl}{$endpointPath}");
        $this->line("  Signature Base: {$baseString}");
        $this->line("  Signature: {$signature}");
        $this->newLine();
        
        $response = \Illuminate\Support\Facades\Http::timeout(120)
            ->withHeaders($headers)
            ->post($baseUrl . $endpointPath, $payload);
        
        if ($response->successful()) {
            return $response->json();
        }
        
        $statusCode = $response->status();
        $errorBody = $response->body();
        
        throw new \RuntimeException("HTTP {$statusCode}: {$errorBody}");
    }
}

