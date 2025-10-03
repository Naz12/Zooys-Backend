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

        try {
            $response = Http::withHeaders([
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

            if ($response->failed()) {
                Log::error('OpenAI API Error: ' . $response->body());
                return 'Sorry, I was unable to generate a summary at this time.';
            }

            $data = $response->json();
            
            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }

            return 'Sorry, I was unable to generate a summary at this time.';
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            return 'Sorry, I was unable to generate a summary at this time.';
        }
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