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

    public function sendPromptOpenAI(string $message, ?string $archivo = null): string
    {
        return app(OpenAiAssistantService::class)->sendPrompt(Auth::id(), $message, $archivo);
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
            'content' => htmlentities($request->get('text'), ENT_QUOTES, 'UTF-8'),
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
        $response = $this->sendPromptOpenAI($request->input('messageText'));

        return response()->json([
            'success' => true,
            'responseText' => $response,
        ]);
    }

    public function censorTextService(Request $request)
    {
        $response = app(OpenAiAssistantService::class)->censorText(Auth::id(), $request->input('messageText'));

        return response()->json([
            'success' => true,
            'responseText' => $response,
        ]);
    }
}
