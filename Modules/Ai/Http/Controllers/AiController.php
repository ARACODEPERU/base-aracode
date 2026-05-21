<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Academic\Services\OpenAiAssistantService;

class AiController extends Controller
{
    public function index()
    {
        $opciones = [
            'post->consulta->request->[user_id, content]',
            'post->censurar->request->[user_id, content]',
            'post->mejorar->request->[user_id, content]',
            'post->recomendar->request->[user_id, content]',
        ];

        return response()->json($opciones);
    }

    public function consulta(Request $request): string
    {
        $userId = Auth::id() ?? $request->input('user_id');
        $message = $request->input('message');

        return $this->send_prompt($userId, $message);
    }

    public function censurar(Request $request): string
    {
        $userId = Auth::id() ?? $request->input('user_id');

        return app(OpenAiAssistantService::class)->censorText($userId, $request->input('message'));
    }

    public function show($id)
    {
        return view('ai::show');
    }

    public function edit($id)
    {
        return view('ai::edit');
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function send_prompt($user_id, $message, $archivo = null): string
    {
        return app(OpenAiAssistantService::class)->sendPrompt($user_id, $message, $archivo);
    }
}
