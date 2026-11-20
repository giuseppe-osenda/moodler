<?php

namespace Tests\Feature;

use App\Services\OpenAiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['services.openai.key' => 'sk-test', 'services.openai.model' => 'gpt-6-luna']);
    }

    /**
     * @param  list<array<string, mixed>>  $content
     * @return array<string, mixed>
     */
    private function responsePayload(array $content, string $status = 'completed'): array
    {
        return [
            'status' => $status,
            'output' => [
                ['type' => 'reasoning', 'summary' => []],
                ['type' => 'message', 'role' => 'assistant', 'content' => $content],
            ],
        ];
    }

    private function ask(): array
    {
        return app(OpenAiClient::class)->json('instructions', 'prompt', 'thing', ['type' => 'object'], reasoningEffort: 'low');
    }

    public function test_it_sends_a_structured_outputs_request_and_decodes_the_answer(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->responsePayload([
            ['type' => 'output_text', 'text' => '{"answer":42}'],
        ]))]);

        $this->assertSame(['answer' => 42], $this->ask());

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer sk-test')
            && $request['model'] === 'gpt-6-luna'
            && $request['reasoning'] === ['effort' => 'low']
            && $request['text']['format']['type'] === 'json_schema'
            && $request['text']['format']['strict'] === true
            && $request['text']['format']['name'] === 'thing');
    }

    public function test_it_fails_when_the_model_refuses(): void
    {
        Http::fake(['api.openai.com/*' => Http::response($this->responsePayload([
            ['type' => 'refusal', 'refusal' => 'No.'],
        ]))]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('refused');

        $this->ask();
    }

    public function test_it_fails_when_the_response_is_incomplete(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'status' => 'incomplete',
            'incomplete_details' => ['reason' => 'max_output_tokens'],
            'output' => [],
        ])]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('max_output_tokens');

        $this->ask();
    }

    public function test_it_fails_on_api_errors(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'Invalid model']], 400)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid model');

        $this->ask();
    }

    public function test_it_requires_an_api_key(): void
    {
        config(['services.openai.key' => null]);
        $this->app->forgetInstance(OpenAiClient::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY');

        $this->ask();
    }
}
