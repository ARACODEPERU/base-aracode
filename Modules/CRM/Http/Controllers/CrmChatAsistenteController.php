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
            'avisoAsistentes' => $this->avisoAsistentes($asistentes, $request, $seleccionado),
        ]);
    }

    /**
     * Aviso para el administrador cuando el chat de consultas no puede operar
     * como un asistente: o no existe ninguno con ese rol, o el que se pidio en la
     * URL ya no lo tiene.
     */
    private function avisoAsistentes($asistentes, Request $request, $seleccionado): ?string
    {
        if ($asistentes->isNotEmpty()) {
            if (! $request->filled('asistente') || $seleccionado) {
                return null;
            }

            return 'El asistente que intentas usar no existe o ya no tiene el rol Asistente. '
                . 'Se abrió el chat como tú mismo; corrige ese usuario o asígnale de nuevo el rol Asistente.';
        }

        return 'No existe ningún usuario con el rol Asistente, así que no hay quien atienda el chat de consultas. '
            . 'Crea un usuario y asígnale el rol Asistente (Roles y permisos) para poder responder como él. '
            . 'Mientras tanto puedes responder como tú mismo.';
    }
}
