<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Vista de chat para admin/Administrador: se elige un asistente y desde ahi se
 * ven sus conversaciones, sus notificaciones y se responde como si fuera el.
 */
class CrmChatAsistenteController extends Controller
{
    public function index(Request $request)
    {
        $asistentes = User::query()
            ->with('person')
            ->role('Asistente')
            ->get()
            ->filter(fn ($user) => $user->person)
            ->map(fn ($user) => [
                'user_id' => $user->id,
                'person_id' => $user->person_id,
                'full_name' => $user->person->full_name,
                'image' => $user->person->image,
            ])
            ->values();

        $seleccionado = null;

        if ($request->filled('asistente')) {
            $seleccionado = $asistentes->firstWhere('person_id', (int) $request->get('asistente'));
        }

        return Inertia::render('CRM::Chat/Dashboard', [
            'P000015' => Parameter::where('parameter_code', 'P000015')->value('value_default'),
            'P000010' => Parameter::where('parameter_code', 'P000010')->value('value_default'),
            'modoAsistentes' => true,
            'asistentes' => $asistentes,
            'asistenteSeleccionado' => $seleccionado,
        ]);
    }
}
