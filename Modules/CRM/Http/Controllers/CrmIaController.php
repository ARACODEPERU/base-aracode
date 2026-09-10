<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Models\Person;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Modules\Academic\Services\OpenAiAssistantService;
use Modules\CRM\Emails\NotifyChatMessage;
use Modules\CRM\Entities\CrmConversation;
use Modules\CRM\Entities\CrmMessage;
use Modules\CRM\Entities\CrmParticipant;
use Modules\CRM\Entities\CrmUser;

class CrmIaController extends Controller
{
    public function clientDashboard()
    {
        $conversationId = null;

        if (request()->has('conv')) {
            $conversationId = request()->get('conv');
        }

        if (request()->get('cont')) {
            $contactId = request()->get('cont');
            $personId = Auth::user()->person_id;

            if ($personId) {
                $conversationId = CrmParticipant::whereIn('person_id', [$contactId, $personId])
                    ->groupBy('conversation_id')
                    ->having(DB::raw('COUNT(DISTINCT user_id)'), '>=', 2)
                    ->value('conversation_id');

                if (!$conversationId) {
                    $conversation = CrmConversation::create([
                        'title' => 'private',
                        'user_id' => Auth::id(),
                        'type_name' => 'chat',
                        'type_action' => null,
                    ]);

                    CrmParticipant::create([
                        'conversation_id' => $conversation->id,
                        'person_id' => $personId,
                        'user_id' => Auth::id(),
                    ]);

                    CrmParticipant::create([
                        'conversation_id' => $conversation->id,
                        'person_id' => $contactId,
                        'user_id' => CrmUser::where('person_id', $contactId)->value('id') ?? null,
                    ]);

                    $conversationId = $conversation->id;
                }
            }
        }

        $participants = CrmParticipant::with('user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', '<>', Auth::id())
            ->get();

        $messages = CrmMessage::where('conversation_id', $conversationId)
            ->orderBy('id')
            ->limit(200)
            ->get();

        return Inertia::render('CRM::Chat/studentDashboard', [
            'messages' => $messages,
            'participants' => $participants,
            'conversationId' => $conversationId,
        ]);
    }

    public function sendPromptOpenAI(string $message, ?string $instructions = null, ?string $archivo = null): string
    {
        return app(OpenAiAssistantService::class)->sendPrompt(Auth::id(), $message, $archivo, $instructions);
    }

    public function sendMessage(Request $request)
    {
        $this->validate($request, [
            'conversationId' => 'required',
            'text' => 'required|string',
        ]);

        $personId = Auth::user()->person_id;
        $conversationId = $request->get('conversationId');

        $participants = CrmParticipant::where('conversation_id', $conversationId)
            ->where('user_id', '<>', Auth::id())
            ->pluck('user_id');

        $message = CrmMessage::create([
            'conversation_id' => $conversationId,
            'person_id' => $personId,
            // Se guarda el HTML tal cual para que las etiquetas se rendericen al mostrarse con v-html
            'content' => $request->get('text'),
            'type' => $request->get('type'),
            'answer_ai' => false,
        ]);

        $recipientNames = CrmUser::whereIn('id', $participants)
            ->with('person')
            ->get()
            ->pluck('person.full_name')
            ->filter()
            ->implode(', ');

        $senderPerson = Person::find($personId);
        $senderName = $senderPerson->full_name;
        if (is_null($senderName) || trim($senderName) === '') {
            $senderName = $senderPerson->short_name;
        }
        if (is_null($senderName) || trim($senderName) === '') {
            $senderName = 'Alumno';
        }
//////aqui sucede error
        $data = [
            'fullName'      => $senderName,
            'message'       => $request->get('text'),
            'recipients'    => $recipientNames ?: 'Administración',
            'created_at'    => $message->created_at->format('d/m/Y h:i A'),
        ];
//////////aqui termina
        $P000013 = Parameter::where('parameter_code', 'P000013')->value('value_default');
        $P000017 = Parameter::where('parameter_code', 'P000017')->value('value_default');

        $conversation = CrmConversation::find($conversationId);

        if ($conversation && $this->shouldSendChatEmailNotification($conversation)) {
            Mail::to($P000013)
                ->cc($P000017)
                ->send(new NotifyChatMessage($data));

            $conversation->update([
                'last_email_notification_at' => now(),
            ]);
        }

        $this->broadcastSend($participants, $message, $personId);

        CrmConversation::find($conversationId)->update([
            'new_message' => true,
        ]);

        return response()->json(['success' => true, 'message' => $message], 201);
    }

    public function broadcastSend($participants, $message, $personId)
    {
        $client = new Client();

        $dom = env('VITE_SOCKET_IO_SERVER', 'https://localhost:3000');
        $url = "{$dom}/api/crm/broadcast";

        $appCodeUnique = env('VITE_APP_CODE', 'ARACODE');

        $channelListen = "message-notification-" . $appCodeUnique;

        try {
            $client->post($url, [
                'json' => [
                    'channelName' => $channelListen,
                    'participants' => $participants,
                    'message' => $message,
                    'ofUserId' => $personId,
                ],
                'verify' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('SocketIOBroadcaster: ' . $e->getMessage());
        }
    }

    private function shouldSendChatEmailNotification(CrmConversation $conversation): bool
    {
        return !$conversation->last_email_notification_at
            || $conversation->last_email_notification_at->lte(now()->subHours(12));
    }

    public function basicQuestionService(Request $request)
    {
        try {
            $messageText = $request->input('messageText');
            $instructions = $request->input('instructions');
            $response = $this->sendPromptOpenAI($messageText, $instructions);

            return response()->json([
                'success' => true,
                'responseText' => $response,
            ]);
        } catch (\Throwable $e) {
            Log::error('CRM basicQuestionService: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function censorTextService(Request $request)
    {
        try {
            $service = app(OpenAiAssistantService::class);

            $questionText = $request->input('messageText');
            $responseText = $request->input('respond') ?: $questionText;

            $censoredQuestion = $this->censorWithFallback($service, $questionText);
            $censoredResponse = $this->censorWithFallback($service, $responseText);

            return response()->json([
                'success' => true,
                'questionText' => $censoredQuestion,
                'responseText' => $censoredResponse,
            ]);
        } catch (\Throwable $e) {
            Log::error('CRM censorTextService: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Censura el texto con la IA, pero si la IA "responde" en lugar de censurar
     * (resultado vacío o mucho más largo que el original) o falla, se usa un
     * censurado local determinista para no guardar contenido inventado.
     */
    private function censorWithFallback(OpenAiAssistantService $service, string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return $text;
        }

        try {
            $censored = $service->censorText(Auth::id(), $text);

            // Alucinación: la IA devolvió una respuesta inventada (mucho más larga)
            if (trim($censored) === '' || mb_strlen($censored) > mb_strlen($text) * 1.5) {
                return $this->localCensor($text);
            }

            return $censored;
        } catch (\Throwable $e) {
            Log::error('CRM censorWithFallback: ' . $e->getMessage());

            return $this->localCensor($text);
        }
    }

    /**
     * Censurado local determinista: DNI/RUC/teléfonos y correos electrónicos.
     */
    private function localCensor(string $text): string
    {
        // DNI (8 dígitos), RUC (11 dígitos) o teléfono (9 dígitos)
        $censored = preg_replace('/\b\d{8,11}\b/', '********', $text) ?? $text;
        // Correos electrónicos
        $censored = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '********', $censored) ?? $censored;

        return $censored;
    }
}
