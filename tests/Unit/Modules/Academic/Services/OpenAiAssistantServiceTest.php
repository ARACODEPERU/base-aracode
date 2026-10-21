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
            'academic.openai.provider_parameter' => null,
            'academic.openai.agent_id_parameter' => null,
            // Sin parametro IA-0000: se usa el respaldo del .env (5 s en pruebas).
            'academic.openai.agent_timeout_parameter' => null,
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
        config(['academic.openai.api_key_parameter' => 'IA-00001']);

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
        config([
            'academic.openai.api_key' => null,
            'academic.openai.api_key_parameter' => 'IA-00001',
        ]);

        // Se simula que el parametro no tiene valor para no depender de la
        // tabla parameters en esta prueba del mensaje de error.
        $service = new class extends OpenAiAssistantService {
            protected function apiKeyFromParameter(): ?string
            {
                return null;
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('IA-00001');

        $service->sendPrompt(11, 'hola');
    }

    public function test_censor_text_uses_fresh_context_without_previous_response_id(): void
    {
        Cache::put('academic:openai-response:8', 'resp_anterior', now()->addHour());

        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_censored',
                'output' => [['content' => [['type' => 'output_text', 'text' => 'texto censurado']]]],
            ]),
        ]);

        $service = app(OpenAiAssistantService::class);
        $result = $service->censorText(8, 'Juan Perez llama al 999 888 777');

        $this->assertSame('texto censurado', $result);

        Http::assertSent(function ($request) {
            return ($request['previous_response_id'] ?? null) === null
                && str_contains($request['input'][0]['content'][0]['text'], 'Juan Perez llama al 999 888 777');
        });

        // La conversacion cacheada no se lee ni se sobreescribe al censurar.
        $this->assertSame('resp_anterior', Cache::get('academic:openai-response:8'));
    }

    public function test_correct_text_returns_corrected_text_with_fresh_context(): void
    {
        Cache::put('academic:openai-response:8', 'resp_anterior', now()->addHour());

        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_corrected',
                'output' => [['content' => [['type' => 'output_text', 'text' => 'Texto corregido con mejor redaccion.']]]],
            ]),
        ]);

        $service = app(OpenAiAssistantService::class);
        $result = $service->correctText(8, 'texto con mala redacion y ortografia');

        $this->assertSame('Texto corregido con mejor redaccion.', $result);

        Http::assertSent(function ($request) {
            $prompt = $request['input'][0]['content'][0]['text'];

            return ($request['previous_response_id'] ?? null) === null
                && str_contains($prompt, 'texto con mala redacion y ortografia')
                && str_contains($prompt, 'NIIF')
                && str_contains($prompt, 'UNICAMENTE el texto corregido');
        });

        // No debe leer ni sobreescribir la conversacion cacheada.
        $this->assertSame('resp_anterior', Cache::get('academic:openai-response:8'));
    }

    public function test_correct_text_spelling_mode_only_asks_for_spelling(): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_spelling',
                'output' => [['content' => [['type' => 'output_text', 'text' => 'Texto con tilde corregida.']]]],
            ]),
        ]);

        $service = app(OpenAiAssistantService::class);
        $result = $service->correctText(21, 'texto con tilde correjida', true);

        $this->assertSame('Texto con tilde corregida.', $result);

        Http::assertSent(function ($request) {
            $prompt = $request['input'][0]['content'][0]['text'];

            return str_contains($prompt, 'UNICAMENTE la ortografia')
                && str_contains($prompt, 'NO reescribas las oraciones')
                && !str_contains($prompt, 'mejora la claridad');
        });
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

    public function test_answers_with_saved_agent_when_agent_id_is_configured(): void
    {
        config([
            'academic.openai.agent_id' => 'agent_test',
            'academic.openai.agent_timeout' => 5,
            'academic.openai.agent_poll_interval_ms' => 200,
        ]);

        Http::fake([
            'api.openai.com/v1/agents/sessions' => Http::response(['id' => 'sess_1']),
            'api.openai.com/v1/agents/sessions/sess_1/items*' => Http::response([
                'data' => [[
                    'id' => 'item_1',
                    'type' => 'message',
                    'role' => 'assistant',
                    'phase' => 'final_answer',
                    'status' => 'completed',
                    'content' => [
                        ['type' => 'output_text', 'text' => 'Respuesta del agente'],
                    ],
                ]],
            ]),
            'api.openai.com/v1/agents/sessions/sess_1' => Http::response([
                'id' => 'sess_1',
                'status' => 'idle',
            ]),
        ]);

        $result = app(OpenAiAssistantService::class)->sendPromptWithAgent(30, '¿Que es el activo?');

        $this->assertTrue($result['agent_used']);
        $this->assertSame('agent', $result['source']);
        $this->assertSame('Respuesta del agente', $result['text']);
        $this->assertNull($result['notice']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/agents/sessions')
                && $request->hasHeader('OpenAI-Beta', 'agents=v1')
                && $request['agent_id'] === 'agent_test'
                && $request['environment']['type'] === 'none'
                && $request['input'][0]['content'][0]['text'] === '¿Que es el activo?';
        });

        $this->assertSame('sess_1', Cache::get('academic:openai-agent-session:30'));
    }

    public function test_uses_agent_id_from_system_parameter(): void
    {
        config([
            'academic.openai.agent_id_parameter' => 'IA-00003',
            'academic.openai.agent_id' => null,
            'academic.openai.agent_timeout' => 5,
            'academic.openai.agent_poll_interval_ms' => 200,
        ]);

        $service = new class extends OpenAiAssistantService {
            protected function parameterValue(?string $parameterCode): ?string
            {
                return $parameterCode === 'IA-00003' ? 'agent_from_parameter' : null;
            }
        };

        Http::fake([
            'api.openai.com/v1/agents/sessions' => Http::response(['id' => 'sess_param']),
            'api.openai.com/v1/agents/sessions/sess_param/items*' => Http::response([
                'data' => [[
                    'id' => 'item_param',
                    'type' => 'message',
                    'role' => 'assistant',
                    'phase' => 'final_answer',
                    'status' => 'completed',
                    'content' => [['type' => 'output_text', 'text' => 'Respuesta parametrizada']],
                ]],
            ]),
            'api.openai.com/v1/agents/sessions/sess_param' => Http::response([
                'id' => 'sess_param',
                'status' => 'idle',
            ]),
        ]);

        $result = $service->sendPromptWithAgent(33, 'consulta');

        $this->assertTrue($result['agent_used']);
        $this->assertSame('Respuesta parametrizada', $result['text']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/agents/sessions')
                && $request['agent_id'] === 'agent_from_parameter';
        });
    }

    public function test_agent_timeout_reads_ia_0000_in_minutes(): void
    {
        config([
            'academic.openai.agent_timeout_parameter' => 'IA-0000',
            'academic.openai.agent_timeout_minutes' => 6,
        ]);

        $service = new class extends OpenAiAssistantService {
            protected function parameterValue(?string $parameterCode): ?string
            {
                return $parameterCode === 'IA-0000' ? '9' : null;
            }
        };

        $this->assertSame(9, $service->agentTimeoutMinutes());
    }

    public function test_agent_timeout_defaults_to_six_minutes_without_parameter(): void
    {
        config([
            'academic.openai.agent_timeout_parameter' => null,
            'academic.openai.agent_timeout_minutes' => 6,
        ]);

        $this->assertSame(6, app(OpenAiAssistantService::class)->agentTimeoutMinutes());
    }

    public function test_agent_enabled_reports_whether_an_agent_is_configured(): void
    {
        config([
            'academic.openai.agent_id_parameter' => null,
            'academic.openai.agent_id' => 'agent_test',
        ]);

        $this->assertTrue(app(OpenAiAssistantService::class)->agentEnabled());

        config(['academic.openai.agent_id' => null]);

        $this->assertFalse(app(OpenAiAssistantService::class)->agentEnabled());
    }

    public function test_provider_reads_system_parameter(): void
    {
        config(['academic.openai.provider_parameter' => 'IA-00002']);

        $service = new class extends OpenAiAssistantService {
            protected function parameterValue(?string $parameterCode): ?string
            {
                return $parameterCode === 'IA-00002' ? '1' : null;
            }
        };

        $this->assertSame('1', $service->provider());
    }

    public function test_provider_defaults_to_gemini_when_parameter_is_missing(): void
    {
        config(['academic.openai.provider_parameter' => 'IA-00002']);

        $service = new class extends OpenAiAssistantService {
            protected function parameterValue(?string $parameterCode): ?string
            {
                return null;
            }
        };

        $this->assertSame('2', $service->provider());
    }

    public function test_falls_back_to_default_model_when_agent_fails(): void
    {
        config([
            'academic.openai.agent_id' => 'agent_test',
            'academic.openai.model' => 'gpt-4.1-mini',
            'academic.openai.agent_timeout' => 5,
            'academic.openai.agent_poll_interval_ms' => 200,
        ]);

        Http::fake([
            'api.openai.com/v1/agents/sessions' => Http::response([
                'error' => ['message' => 'You do not have access to this agent.'],
            ], 403),
            'api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_fallback',
                'output' => [
                    ['content' => [['type' => 'output_text', 'text' => 'Respuesta del modelo']]],
                ],
            ]),
        ]);

        $result = app(OpenAiAssistantService::class)->sendPromptWithAgent(31, 'consulta');

        $this->assertFalse($result['agent_used']);
        $this->assertSame('model', $result['source']);
        $this->assertSame('Respuesta del modelo', $result['text']);
        $this->assertStringContainsString('Agente de OpenAI no respondió', $result['notice']);
        $this->assertStringContainsString('gpt-4.1-mini', $result['notice']);
        $this->assertNull(Cache::get('academic:openai-agent-session:31'));
    }

    public function test_reuses_agent_session_for_follow_up_questions(): void
    {
        config([
            'academic.openai.agent_id' => 'agent_test',
            'academic.openai.agent_timeout' => 5,
            'academic.openai.agent_poll_interval_ms' => 200,
        ]);

        $answer = fn (string $id, string $text) => [
            'id' => $id,
            'type' => 'message',
            'role' => 'assistant',
            'phase' => 'final_answer',
            'status' => 'completed',
            'content' => [['type' => 'output_text', 'text' => $text]],
        ];

        Http::fake([
            'api.openai.com/v1/agents/sessions' => Http::response(['id' => 'sess_2']),
            'api.openai.com/v1/agents/sessions/sess_2/items*' => Http::sequence()
                ->push(['data' => [$answer('item_1', 'Primera respuesta')]])
                ->push(['data' => [$answer('item_1', 'Primera respuesta')]])
                ->push(['data' => [$answer('item_2', 'Segunda respuesta')]]),
            'api.openai.com/v1/agents/sessions/sess_2' => Http::response([
                'id' => 'sess_2',
                'status' => 'idle',
            ]),
            'api.openai.com/v1/agents/sessions/sess_2/events' => Http::response(['ok' => true]),
        ]);

        $service = app(OpenAiAssistantService::class);

        $first = $service->sendPromptWithAgent(32, 'primera pregunta');
        $second = $service->sendPromptWithAgent(32, 'segunda pregunta');

        $this->assertSame('Primera respuesta', $first['text']);
        $this->assertSame('Segunda respuesta', $second['text']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/agents/sessions/sess_2/events')
                && $request['events'][0]['type'] === 'agent.session.input.message'
                && $request['events'][0]['input'][0]['content'][0]['text'] === 'segunda pregunta';
        });
    }
}
