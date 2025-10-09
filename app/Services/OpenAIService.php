<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    public function generateResponse($prompt)
    {
        $apiKey = config('services.openai.api_key');
        $url = config('services.openai.url');
        $model = config('services.openai.model', 'gpt-3.5-turbo');
        $maxTokens = (int) config('services.openai.max_tokens', 1000);
        $temperature = (float) config('services.openai.temperature', 0.7);

        // Retry configuration
        $maxRetries = 3;
        $baseDelay = 1; // seconds
        $timeout = 30; // seconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("OpenAI API Attempt {$attempt}/{$maxRetries}", [
                    'model' => $model,
                    'prompt_length' => strlen($prompt)
                ]);

                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])->post($url, [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['choices'][0]['message']['content'])) {
                        Log::info("OpenAI API Success on attempt {$attempt}", [
                            'response_length' => strlen($data['choices'][0]['message']['content'])
                        ]);
                        return trim($data['choices'][0]['message']['content']);
                    }
                } else {
                    Log::warning("OpenAI API failed on attempt {$attempt}", [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                }

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::warning("OpenAI API Connection Error on attempt {$attempt}", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);
            } catch (\Exception $e) {
                Log::error("OpenAI API Error on attempt {$attempt}", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);
            }

            // If not the last attempt, wait before retrying
            if ($attempt < $maxRetries) {
                $delay = $baseDelay * pow(2, $attempt - 1); // Exponential backoff
                Log::info("Waiting {$delay} seconds before retry...");
                sleep($delay);
            }
        }

        Log::error('OpenAI API failed after all retries');
        return 'Sorry, I was unable to process your request after multiple attempts. Please try again later.';
    }

    /**
     * Analyze image using OpenAI Vision API
     */
    public function analyzeImage($imagePath, $prompt)
    {
        $apiKey = config('services.openai.api_key');
        $url = config('services.openai.url');
        $model = config('services.openai.vision_model', 'gpt-4o');
        $maxTokens = (int) config('services.openai.max_tokens', 2000);
        $temperature = (float) config('services.openai.temperature', 0.3);

        // Retry configuration
        $maxRetries = 3;
        $baseDelay = 2; // seconds
        $timeout = 60; // seconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("OpenAI Vision API Attempt {$attempt}/{$maxRetries}", [
                    'image_path' => $imagePath,
                    'model' => $model
                ]);
            // Check if image file exists
            if (!file_exists($imagePath)) {
                throw new \Exception('Image file not found: ' . $imagePath);
            }

            // Get image data
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);
            
            // Determine image format
            $imageFormat = 'jpeg';
            if (strpos($mimeType, 'png') !== false) {
                $imageFormat = 'png';
            } elseif (strpos($mimeType, 'gif') !== false) {
                $imageFormat = 'gif';
            } elseif (strpos($mimeType, 'webp') !== false) {
                $imageFormat = 'webp';
            }

                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])->post($url, [
                        'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/' . $imageFormat . ';base64,' . $imageData
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['choices'][0]['message']['content'])) {
                        Log::info("OpenAI Vision API Success on attempt {$attempt}", [
                            'response_length' => strlen($data['choices'][0]['message']['content'])
                        ]);
                        return trim($data['choices'][0]['message']['content']);
                    }
                } else {
                    Log::warning("OpenAI Vision API failed on attempt {$attempt}", [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                }

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::warning("OpenAI Vision API Connection Error on attempt {$attempt}", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);
            } catch (\Exception $e) {
                Log::error("OpenAI Vision API Error on attempt {$attempt}", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);
            }

            // If not the last attempt, wait before retrying
            if ($attempt < $maxRetries) {
                $delay = $baseDelay * pow(2, $attempt - 1); // Exponential backoff
                Log::info("Waiting {$delay} seconds before retry...");
                sleep($delay);
            }
        }

        Log::error('OpenAI Vision API failed after all retries');
        return 'Sorry, I was unable to analyze the image after multiple attempts. Please try again later.';
    }

    /**
     * Generate embeddings using OpenAI
     */
    public function generateEmbedding($text)
    {
        $apiKey = config('services.openai.api_key');
        $url = 'https://api.openai.com/v1/embeddings';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'model' => 'text-embedding-ada-002',
                'input' => $text
            ]);

            if ($response->failed()) {
                Log::error('OpenAI Embeddings API Error: ' . $response->body());
                throw new \Exception('Failed to generate embedding');
            }

            $data = $response->json();
            
            if (isset($data['data'][0]['embedding'])) {
                return $data['data'][0]['embedding'];
            }

            throw new \Exception('No embedding returned from OpenAI');
        } catch (\Exception $e) {
            Log::error('OpenAI Embeddings API Error: ' . $e->getMessage());
            throw $e;
        }
    }
}