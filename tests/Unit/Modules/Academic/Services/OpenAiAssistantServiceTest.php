<?php

namespace Tests\Unit\Modules\Academic\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Academic\Services\OpenAiAssistantService;
use RuntimeException;
use Tests\TestCase;

class OpenAiAssistantServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // Por defecto, resolver la key por .env para no depender de la base de datos.
        config([
            'academic.openai.api_key' => 'sk-test-env-key',
            'academic.openai.api_key_parameter' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_sends_prompt_to_responses_api_and_returns_text(): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_123',
                'output' => [
                    [
                        'content' => [
                            ['type' => 'output_text', 'text' => 'La respuesta del docente'],
                        ],
                    ],
                ],
            ]),
        ]);

        $service = app(OpenAiAssistantService::class);
        $answer = $service->sendPrompt(7, '¿Que es una cuenta de activo?');

        $this->assertSame('La respuesta del docente', $answer);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/responses')
                && $request->header('Authorization')[0] === 'Bearer sk-test-env-key'
                && $request['model'] === 'gpt-4.1-mini'
                && $request['input'][0]['content'][0]['text'] === '¿Que es una cuenta de activo?';
        });
    }

    public function test_resolves_api_key_from_system_parameter(): void
    {
        config(['academic.openai.api_key_parameter' => 'P000025']);

        $service = new class extends OpenAiAssistantService {
            protected function apiKeyFromParameter(): ?string
            {
                return 'sk-test-parameter-key';
            }
        };

        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_param',
                'output' => [['content' => [['type' => 'output_text', 'text' => 'ok']]]],
            ]),
        ]);

        $answer = $service->sendPrompt(12, 'hola');

        $this->assertSame('ok', $answer);

        Http::assertSent(function ($request) {
            return $request->header('Authorization')[0] === 'Bearer sk-test-parameter-key';
        });
    }

    public function test_chains_conversation_with_previous_response_id(): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => Http::sequence()
                ->push(['id' => 'resp_1', 'output' => [['content' => [['type' => 'output_text', 'text' => 'primera']]]]])
                ->push(['id' => 'resp_2', 'output' => [['content' => [['type' => 'output_text', 'text' => 'segunda']]]]]),
        ]);

        $service = app(OpenAiAssistantService::class);
        $service->sendPrompt(8, 'primera pregunta');
        $service->sendPrompt(8, 'segunda pregunta');

        Http::assertSent(function ($request) {
            return ($request['previous_response_id'] ?? null) === 'resp_1'
                && $request['input'][0]['content'][0]['text'] === 'segunda pregunta';
        });
    }

    public function test_retries_without_history_when_previous_response_expired(): void
    {
        Cache::put('academic:openai-response:9', 'resp_expired', now()->addHour());

        Http::fake([
            'api.openai.com/v1/responses' => Http::sequence()
                ->push([
                    'error' => ['message' => 'No previous response found for this previous_response_id.'],
                ], 400)
                ->push(['id' => 'resp_ok', 'output' => [['content' => [['type' => 'output_text', 'text' => 'recuperado']]]]]),
        ]);

        $service = app(OpenAiAssistantService::class);
        $answer = $service->sendPrompt(9, 'otra pregunta');

        $this->assertSame('recuperado', $answer);
        $this->assertSame('resp_ok', Cache::get('academic:openai-response:9'));
    }

    public function test_surfaces_openai_error_message_when_key_is_invalid(): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'error' => ['message' => 'Incorrect API key provided.'],
            ], 401),
        ]);

        $service = app(OpenAiAssistantService::class);

        try {
            $service->sendPrompt(10, 'hola');
            $this->fail('Se esperaba RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Incorrect API key provided.', $e->getMessage());
            $this->assertStringContainsString('401', $e->getMessage());
        }
    }

    public function test_throws_clear_error_when_parameter_and_env_key_are_missing(): void
    {
        config(['academic.openai.api_key' => null]);

        $service = app(OpenAiAssistantService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('P000025');

        $service->sendPrompt(11, 'hola');
    }

    public function test_sanitizes_invisible_characters_from_api_key(): void
    {
        // Key pegada con espacio, NBSP y zero-width space invisibles
        config(['academic.openai.api_key' => "sk-test \u{00A0}key\u{200B}x  "]);

        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_clean',
                'output' => [['content' => [['type' => 'output_text', 'text' => 'ok']]]],
            ]),
        ]);

        app(OpenAiAssistantService::class)->sendPrompt(13, 'hola');

        Http::assertSent(function ($request) {
            return $request->header('Authorization')[0] === 'Bearer sk-testkeyx';
        });
    }
}
