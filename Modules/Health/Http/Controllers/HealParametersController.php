<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Services\DoctorGoogleCalendarService;
use Modules\Health\Services\GoogleCalendarSyncService;
use Modules\Health\Support\GoogleCalendarOptions;
use Modules\Health\Support\ResolvesDoctorContext;

/**
 * Pestaña Parámetros de Salud.
 *
 * Desde aqui cada doctor enlaza o desenlaza SU agenda de Google. El
 * administrador ve y gestiona a todos los doctores, y es el unico que cambia
 * las opciones de configuracion del enlace.
 */
class HealParametersController extends Controller
{
    use ResolvesDoctorContext;

    public function __construct(
        private readonly DoctorGoogleCalendarService $doctors,
        private readonly GoogleCalendarSyncService $sync,
    ) {
    }

    public function index(): Response
    {
        $canManageAll = $this->canChooseDoctor();
        $own = $this->currentDoctor();

        $doctors = HealDoctor::with('person')
            ->orderBy('id')
            ->when(! $canManageAll, fn ($query) => $query->whereKey($own?->id ?? 0))
            ->get()
            ->map(fn (HealDoctor $doctor) => [
                'code' => $doctor->id,
                'name' => $doctor->person?->full_name,
                'specialty' => $doctor->specialty,
                'isOwn' => $own !== null && (int) $own->id === (int) $doctor->id,
                'google' => $this->doctors->status($doctor->id),
            ])
            ->values();

        $principal = $this->sync->account();

        return Inertia::render('Health::Parametros/Index', [
            'doctors' => $doctors,
            'canManageAll' => $canManageAll,
            'canSelfConnect' => $canManageAll || $this->doctors->allowSelfConnect(),
            'options' => GoogleCalendarOptions::read(),
            'principal' => [
                'connected' => ($principal['email'] ?? null) !== null,
                'email' => $principal['email'] ?? null,
            ],
        ]);
    }

    /**
     * Guarda las opciones de configuracion del enlace (solo administrador).
     */
    public function updateGoogleOptions(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'allow_doctor_connection' => ['required', 'boolean'],
            'principal_fallback' => ['required', 'boolean'],
            'confirm_minutes' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        GoogleCalendarOptions::save($data);

        return back()->with('message', 'Opciones de Google Calendar guardadas.');
    }
}
