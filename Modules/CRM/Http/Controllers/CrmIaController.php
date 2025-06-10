<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Modules\CRM\Entities\CrmConversation;
use Modules\CRM\Entities\CrmMessage;
use Modules\CRM\Entities\CrmParticipant;
use Modules\CRM\Entities\CrmUser;
use Modules\CRM\Events\SendMessage;
use Illuminate\Support\Facades\Mail;
use Modules\CRM\Emails\NotifyChatMessage;

class CrmIaController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function clientDashboard()
    {

        if (request()->has('conv')) {
            $conversationId = request()->get('conv');
        }
        if (request()->get('cont')) {
            $contactId = request()->get('cont');
            $personId = Auth::user()->person_id;
            if ($personId) {
                // Buscar conversación existente
                $conversationId = CrmParticipant::whereIn('person_id', [$contactId, $personId])
                    ->groupBy('conversation_id')
                    ->having(DB::raw('COUNT(DISTINCT user_id)'), '>=', 2)
                    ->value('conversation_id');
                if (!$conversationId) {
                    // Crear nueva conversación
                    $conversation = CrmConversation::create([
                        'title' => 'private',
                        'user_id' => Auth::id(),
                        'type_name' => 'chat',
                        'type_action' => null
                    ]);

                    // Agregar participantes
                    CrmParticipant::create([
                        'conversation_id' => $conversation->id,
                        'person_id' => $personId,
                        'user_id' => Auth::id()
                    ]);

                    CrmParticipant::create([
                        'conversation_id' => $conversation->id,
                        'person_id' => $contactId,
                        'user_id' => CrmUser::where('person_id', $contactId)->value('id') ?? null
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

        //dd($messages);
        return Inertia::render('CRM::Chat/studentDashboard', [
            'messages' => $messages,
            'participants' => $participants,
            'conversationId' => $conversationId
        ]);
    }

    public function sendPromptOpenAI($message)
    {

        $user_id = Auth::id();
        $archivo = null;

        $port = env('AI__PORT', 5000);
        // URL del servidor Flask
        $url = 'http://127.0.0.1:' . $port . '/assistant_ai';

        $retryCount = 5; // Número de reintentos
        $retryDelay = 300; // Retardo entre reintentos en milisegundos

        for ($i = 0; $i < $retryCount; $i++) {
            try {
                // Enviar la solicitud POST
                $response = Http::asForm()->timeout(30)->post($url, [
                    'user_id' => $user_id,
                    'message' => $message,
                    'archivo' => $archivo,
                ]);

                // Verificar si la solicitud fue exitosa
                if ($response->successful()) {
                    // Devolver la respuesta del servidor Flask
                    $data = response()->json($response->json())->original; // Accede al contenido
                    return $data['response']; // Accede al campo 'response'
                } else {
                    // Manejar el error
                    return response()->json([
                        'error' => 'Error al comunicarse con el servidor de AI',
                        'details' => $response->body(),
                    ], $response->status());
                }
            } catch (\Exception $e) {
                // Capturar excepciones de tiempo de espera agotado y realizar un nuevo intento
                if ($i < $retryCount - 1 && $e instanceof \Illuminate\Http\Client\RequestException && $e->getCode() === 28) {
                    usleep($retryDelay * 1000); // Retardo en microsegundos
                } else {
                    throw $e; // Lanzar la excepción si no es un error de tiempo de espera agotado o se ha excedido el número de reintentos
                }
            }
        }

        // Si todos los reintentos fallan, devolver la respuesta de la última solicitud
        return $response;
    }

    public function sendMessage(Request $request)
    {
        $this->validate(
            $request,
            [
                'conversationId' => 'required',
                'text' => 'required|string',
            ]
        );

        $personId = Auth::user()->person_id;


        $conversationId = $request->get('conversationId');

        // buscamos a todos los participantes de la conversacion ecepto el que lo envia
        $participants = CrmParticipant::where('conversation_id', $conversationId)
            ->where('user_id', '<>', Auth::id())
            ->pluck('user_id');
        // Crear el mensaje
        $message = CrmMessage::create([
            'conversation_id' => $conversationId,
            'person_id' => $personId,
            'content' => htmlentities($request->get('text'), ENT_QUOTES, "UTF-8"),
            'type' => $request->get('type'),
            'answer_ai' => false
        ]);

        $data = [
            'fullName' => Person::find($personId)->value('full_name'),
            'message' => $request->get('text')
        ];

        $P000013 = Parameter::where('parameter_code', 'P000013')->value('value_default');
        $P000017 = Parameter::where('parameter_code', 'P000017')->value('value_default');

        Mail::to($P000013)
            ->cc($P000017)
            ->send(new NotifyChatMessage($data));
        // Devolver la conversación con los mensajes
        broadcast(new SendMessage($participants, $message, ['ofUserId' => $personId], $conversationId));

        CrmConversation::find($conversationId)->update([
            'new_message' => true,
        ]);

        return response()->json(['success' => true, 'message' => $message], 201);
    }

    /**
     * Show the specified resource.
     */

    public function basicQuestionService(Request $request)
    {
        $user_id = Auth::id();
        $message = $request->input('messageText');
        $response = $this->sendPromptOpenAI($user_id, $message);
        return response()->json([
            'success' => true,
            'responseText' => $response
        ]);
    }

    public function censorTextService(Request $request)
    {
        $user_id = Auth::id();
        $cens = "por favor censura con asteriscos los nombres personales y de empresas privadas en el siguiente texto, las públicas no; solo responde lo que pedí sin palabras previas o saludos: ";
        $message = $cens . $request->input('messageText');
        $response = $this->sendPromptOpenAI($user_id, $message);
        return response()->json([
            'success' => true,
            'responseText' => $response
        ]);
    }
}
