<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Modules\CRM\Entities\CrmConversation;
use Modules\CRM\Entities\CrmMessage;
use Modules\CRM\Entities\CrmParticipant;
use Modules\CRM\Events\SendNotification;
use Modules\CRM\Http\Controllers\Concerns\ResuelveIdentidadAsistente;

class CrmConversationController extends Controller
{
    use ResuelveIdentidadAsistente;

    /**
     * Display a listing of the resource.
     */
    public function getConversations(Request $request)
    {
        // Los administradores reciben una sola notificacion agregada que los
        // lleva al chat de asistentes ("Mensaje para tus Asistentes").
        if ($this->puedeVerComoAsistentes()) {
            return $this->notificacionAgregadaAsistentes();
        }

        // Identidad efectiva: el asistente suplantado cuando un admin entra
        // desde "Chat de consultas".
        $persomId = $this->personaEfectiva($request);

        $latestReceivedMessage = CrmMessage::select('crm_messages.*')
            ->joinSub(
                CrmMessage::select('conversation_id', DB::raw('MAX(id) as last_message_id'))
                    ->where('person_id', '<>', $persomId)
                    ->groupBy('conversation_id'),
                'latest_messages',
                function ($join) {
                    $join->on('crm_messages.id', '=', 'latest_messages.last_message_id');
                }
            );

        $baseConversations = CrmParticipant::join('crm_conversations', 'crm_participants.conversation_id', 'crm_conversations.id')
            ->joinSub($latestReceivedMessage, 'last_message', function ($join) {
                $join->on('crm_conversations.id', '=', 'last_message.conversation_id');
            })
            ->join('people', 'last_message.person_id', 'people.id')
            ->where('crm_participants.person_id', $persomId);

        $total_new = (clone $baseConversations)
            ->where('crm_conversations.new_message', true)
            ->count();

        $conversations = $baseConversations
            ->select(
                'crm_conversations.new_message',
                'crm_conversations.id',
                'last_message.id as message_id',
                'last_message.person_id as message_person_id',
                'last_message.content as message_content',
                'last_message.type as message_type',
                'last_message.created_at as message_created_at',
                'people.full_name',
                'people.image',
                'people.email',
            )
            ->orderByDesc('last_message.created_at')
            ->limit(5)
            ->get();

        $formattedConversations = $conversations->map(function ($conversation) {
            $preview = '';
            $who = '<strong class="text-sm mr-1">' . $conversation->full_name . ': </strong>';

            if ($conversation->message_type == 'text') {
                $content = html_entity_decode($conversation->message_content, ENT_QUOTES, "UTF-8");
                $strcontent = strlen($content) > 30 ? substr($content, 0, 30) . ' ...' : $content;
                $preview = ($who . $strcontent);
            } else if ($conversation->message_type == 'audio') {
                $preview = ($who . 'audio');
            } else if ($conversation->message_type == 'link') {
                $preview = ($who . 'enlace');
            } else {
                $preview = ($who . 'archivo');
            }

            $conversation->time = timeElapsed($conversation->message_created_at);
            $conversation->person_id = $conversation->message_person_id;
            $conversation->preview = $preview;
            $conversation->new_order = $conversation->message_created_at;

            return $conversation;
        });

        return response()->json([
            'conversations' => $formattedConversations->values(),
            'totalNew' => $total_new
        ]);
    }


    /**
     * Notificacion unica para el admin: cuantas conversaciones estan esperando
     * respuesta de algun asistente, con enlace a la vista de asistentes.
     */
    private function notificacionAgregadaAsistentes()
    {
        $asistentesPersonIds = User::query()
            ->role('Asistente')
            ->pluck('person_id')
            ->filter()
            ->unique()
            ->values();

        if ($asistentesPersonIds->isEmpty()) {
            return response()->json(['conversations' => [], 'totalNew' => 0]);
        }

        $latestMessage = CrmMessage::select('conversation_id', DB::raw('MAX(id) as last_message_id'))
            ->groupBy('conversation_id');

        $conversations = CrmParticipant::join('crm_conversations', 'crm_participants.conversation_id', 'crm_conversations.id')
            ->joinSub($latestMessage, 'last_message', function ($join) {
                $join->on('crm_conversations.id', '=', 'last_message.conversation_id');
            })
            ->join('crm_messages as last_message_data', 'last_message_data.id', '=', 'last_message.last_message_id')
            ->join('people', 'last_message_data.person_id', 'people.id')
            ->whereIn('crm_participants.person_id', $asistentesPersonIds)
            ->where('crm_conversations.new_message', true)
            // Solo cuentan los mensajes que estan esperando a un asistente.
            ->whereNotIn('last_message_data.person_id', $asistentesPersonIds)
            ->select(
                'crm_conversations.id',
                'last_message_data.created_at as message_created_at',
                'people.full_name',
                'people.image'
            )
            ->orderByDesc('last_message_data.created_at')
            ->get()
            ->unique('id')
            ->values();

        if ($conversations->isEmpty()) {
            return response()->json(['conversations' => [], 'totalNew' => 0]);
        }

        $ultimo = $conversations->first();

        return response()->json([
            'conversations' => [[
                'id' => 'asistentes',
                'is_assistant_aggregate' => true,
                'url' => route('crm_chat_asistente'),
                'full_name' => 'Mensaje para tus Asistentes',
                'image' => null,
                'preview' => '<strong class="text-sm mr-1">Mensaje para tus Asistentes</strong>',
                'time' => timeElapsed($ultimo->message_created_at),
                'total' => $conversations->count(),
            ]],
            'totalNew' => $conversations->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('crm::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('crm::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateConversationStatus($id)
    {
        CrmConversation::find($id)->update([
            'status' => 'Recibido',
            'new_message' => false
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
