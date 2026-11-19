<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Ai\Services\AiManager;
use Modules\Ai\Support\IaSettings;
use Throwable;

/**
 * Prueba de una consulta de IA desde el módulo, sin pasar por los chats.
 */
class AiPruebaController extends Controller
{
    public function index()
    {
        return Inertia::render('Ai::Probar/Index', [
            'provider' => IaSettings::provider(),
            'openAiModel' => IaSettings::openAiModel(),
            'geminiModel' => IaSettings::geminiModel(),
            'openAiConfigured' => IaSettings::openAiApiKey() !== null,
            'geminiConfigured' => IaSettings::geminiApiKey() !== null,
            'providers' => [
                ['value' => 'openai', 'label' => 'OpenAI'],
                ['value' => 'gemini', 'label' => 'Gemini AI'],
            ],
        ]);
    }

    public function enviar(Request $request, AiManager $manager)
    {
        $data = $request->validate([
            'mensaje' => 'required|string|max:2000',
            'provider' => 'nullable|in:openai,gemini',
        ]);

        try {
            $result = $manager->chat(auth()->id(), $data['mensaje'], [
                'provider' => $data['provider'] ?? null,
                'screen' => 'ia:prueba',
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'provider' => $result->provider,
            'model' => $result->model,
            'respuesta' => $result->text,
            'prompt_tokens' => $result->promptTokens,
            'completion_tokens' => $result->completionTokens,
            'total_tokens' => $result->totalTokens,
            'cost_usd' => $result->costUsd,
        ]);
    }
}
