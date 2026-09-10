<?php

namespace Modules\CRM\Http\Controllers;

use App\Models\Person;
use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\CRM\Entities\CrmConversation;
use Modules\CRM\Entities\CrmMessage;
use Modules\CRM\Entities\CrmParticipant;

class CrmChatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $P000015;
    protected $P000010;

    public function __construct()
    {
        $this->P000015 = Parameter::where('parameter_code', 'P000015')->value('value_default');
        $this->P000010  = Parameter::where('parameter_code', 'P000010')->value('value_default');
    }

    public function index(Request $request)
    {
        return Inertia::render('CRM::Chat/Dashboard', [
            'P000015' => $this->P000015,
            'P000010' => $this->P000010
        ]);
    }

    public function getContacts()
    {
        $persomId = Auth::user()->person_id;

        // Subquery: obtener la fecha del último mensaje por conversación entre personas distintas
        $latestMessageSubquery = DB::table('crm_messages as m')
            ->select('c1.person_id as contact_id', DB::raw('MAX(m.created_at) as last_message_at'))
            ->join('crm_conversations as c', 'm.conversation_id', '=', 'c.id')
            ->join('crm_participants as c1', function ($join) use ($persomId) {
                $join->on('c1.conversation_id', '=', 'c.id')
                    ->where('c1.person_id', '!=', $persomId);
            })
            ->join('crm_participants as c2', function ($join) use ($persomId) {
                $join->on('c2.conversation_id', '=', 'c.id')
                    ->where('c2.person_id', '=', $persomId);
            })
            ->groupBy('contact_id');

        // Query principal: personas con cuenta de usuario o con alguna conversación conmigo
        $persons = Person::where(function ($query) use ($persomId) {
            // Tiene cuenta de usuario (se le puede iniciar un chat aunque aún no haya conversación)
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.person_id', 'people.id');
            })
            // o tiene una conversación conmigo (aunque no tenga cuenta de usuario)
            ->orWhereExists(function ($sub) use ($persomId) {
                $sub->select(DB::raw(1))
                    ->from('crm_participants as cp')
                    ->whereColumn('cp.person_id', 'people.id')
                    ->whereExists(function ($conv) use ($persomId) {
                        $conv->select(DB::raw(1))
                            ->from('crm_participants as me')
                            ->whereColumn('me.conversation_id', 'cp.conversation_id')
                            ->where('me.person_id', $persomId);
                    });
            });
        })
            ->where('people.id', '<>', $persomId)
            ->leftJoinSub($latestMessageSubquery, 'latest', function ($join) {
                $join->on('people.id', '=', 'latest.contact_id');
            })
            ->select('people.*', 'latest.last_message_at');

        // Filtro por búsqueda
        if (request()->has('search')) {
            $persons->where('people.full_name', 'like', '%' . request()->input('search') . '%');
        }

        // Primero las conversaciones por último mensaje (más recientes) y luego el resto por fecha de creación
        $persons = $persons->orderByRaw('(latest.last_message_at IS NULL) ASC')
            ->orderByDesc(DB::raw('COALESCE(latest.last_message_at, people.created_at)'))
            ->paginate(5);

        // Mapear resultados para añadir campos extra
        $formattedPersons = $persons->getCollection()->map(function ($person) use ($persomId) {

            $conversationId = CrmParticipant::whereIn('person_id', [$persomId, $person->id])
                ->groupBy('conversation_id')
                ->having(DB::raw('COUNT(DISTINCT person_id)'), '>=', 2)
                ->value('conversation_id');

            $message_active = false;
            $message_last = CrmMessage::where('conversation_id', $conversationId)
                ->orderByDesc('id')
                ->first();

            $preview = '';
            if ($message_last) {
                $who = $message_last->person_id == $persomId ? 'Tú: ' : '';

                switch ($message_last->type) {
                    case 'text':
                        $preview = $who . $message_last->content;
                        break;
                    case 'audio':
                        $preview = $who . 'audio';
                        break;
                    case 'link':
                        $preview = $who . 'enlace';
                        break;
                    default:
                        $preview = $who . 'archivo';
                        break;
                }

                $see = CrmConversation::where('id', $conversationId)->value('new_message');
                $message_active = $message_last->person_id != $persomId ? $see : false;
            }

            $person->conversationId = $conversationId;
            $person->newMessage = $message_active ?? false;
            $person->userId = $person->id;
            $person->time = $message_last ? timeElapsed($message_last->created_at) : null;
            $person->preview = $message_last ? $preview : null;
            $person->messages = [];
            $person->active = true;
            $person->new_order = $message_last ? $message_last->created_at : null;

            return $person;
        });

        // Reemplazar la colección paginada con la formateada
        $persons->setCollection($formattedPersons);

        return response()->json($persons);
    }
}
