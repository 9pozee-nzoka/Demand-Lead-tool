<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAIService - Wrapper for OpenAI API integration
 * 
 * Provides chat completions for intent classification, entity extraction,
 * and other AI-powered features
 */
class OpenAIService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.openai.com/v1';
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', '');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Send a chat completion request
     */
    public function chat(array $messages, array $options = []): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("OpenAI API key not configured");
        }

        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500,
        ], $options);

        Log::info("Sending OpenAI chat request", [
            'model' => $payload['model'],
            'message_count' => count($messages),
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->apiUrl . '/chat/completions', $payload);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    "OpenAI API request failed: " . $response->body()
                );
            }

            $data = $response->json();
            
            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \RuntimeException("Invalid OpenAI response format");
            }

            $content = $data['choices'][0]['message']['content'];

            Log::info("OpenAI chat request successful", [
                'tokens_used' => $data['usage']['total_tokens'] ?? null,
            ]);

            return $content;
        } catch (\Throwable $e) {
            Log::error("OpenAI API request failed", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract JSON from response (handles markdown code blocks)
     */
    public function extractJson(string $response): ?array
    {
        // Try to parse directly first
        $decoded = json_decode($response, true);
        if ($decoded !== null) {
            return $decoded;
        }

        // Try to extract from markdown code block
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Try to extract from any code block
        if (preg_match('/```\s*(.*?)\s*```/s', $response, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Generate embeddings for text
     */
    public function embeddings(string $text): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("OpenAI API key not configured");
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(30)
        ->post($this->apiUrl . '/embeddings', [
            'model' => 'text-embedding-ada-002',
            'input' => $text,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "OpenAI embeddings request failed: " . $response->body()
            );
        }

        $data = $response->json();
        
        return $data['data'][0]['embedding'] ?? [];
    }

    /**
     * Check if service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get current model
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Set model
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }
}
