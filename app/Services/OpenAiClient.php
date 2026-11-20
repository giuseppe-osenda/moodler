<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal OpenAI Responses API client that returns schema-validated JSON (Structured Outputs).
 */
class OpenAiClient
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl,
    ) {}

    /**
     * Ask the model for a JSON object matching the given JSON schema.
     *
     * @param  array<string, mixed>  $schema
     * @param  'none'|'low'|'medium'|'high'  $reasoningEffort
     * @return array<string, mixed>
     */
    public function json(string $instructions, string $prompt, string $schemaName, array $schema, string $reasoningEffort = 'none'): array
    {
        if (blank($this->apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured (OPENAI_API_KEY).');
        }

        $response = Http::withToken($this->apiKey)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(60)
            ->retry(2, 500, throw: false)
            ->post('/responses', [
                'model' => $this->model,
                'instructions' => $instructions,
                'input' => $prompt,
                'reasoning' => ['effort' => $reasoningEffort],
                'store' => false,
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => $schemaName,
                        'schema' => $schema,
                        'strict' => true,
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI request failed: '.$response->json('error.message', $response->status()));
        }

        $decoded = json_decode($this->outputText($response), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI returned an unexpected response.');
        }

        return $decoded;
    }

    /**
     * Extract the assistant's text from a Responses API payload.
     */
    private function outputText(Response $response): string
    {
        if ($response->json('status') === 'incomplete') {
            throw new RuntimeException('OpenAI response was incomplete: '.$response->json('incomplete_details.reason', 'unknown reason'));
        }

        foreach ($response->json('output', []) as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $part) {
                if (($part['type'] ?? null) === 'refusal') {
                    throw new RuntimeException('OpenAI refused the request: '.($part['refusal'] ?? ''));
                }

                if (($part['type'] ?? null) === 'output_text') {
                    return (string) ($part['text'] ?? '');
                }
            }
        }

        throw new RuntimeException('OpenAI response contained no text output.');
    }
}
