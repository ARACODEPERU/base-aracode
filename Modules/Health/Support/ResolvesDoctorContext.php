<?php

namespace Modules\Health\Support;

use Illuminate\Support\Facades\Auth;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealPatient;

/**
 * Resuelve el doctor del usuario autenticado y su alcance sobre las agendas.
 *
 * Misma semantica que HealAgendaController y HealAttentionController: los
 * roles administrativos pueden elegir a cualquier doctor; el resto queda
 * atado a su propio registro en heal_doctors (por user_id o por person_id).
 */
trait ResolvesDoctorContext
{
    /** Roles con permiso para agendar en la agenda de cualquier doctor. */
    protected function canChooseDoctor(): bool
    {
        $user = Auth::user();

        return !$user || $user->hasAnyRole(['admin', 'Admin', 'Administrador', 'webAdmin']);
    }

    /** Doctor vinculado al usuario autenticado. */
    protected function currentDoctor(): ?HealDoctor
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return HealDoctor::with('person')
            ->where('user_id', $user->id)
            ->when($user->person_id, function ($query) use ($user) {
                $query->orWhere('person_id', $user->person_id);
            })
            ->first();
    }

    /** Formato de opcion que consumen los Multiselect del frontend. */
    protected function doctorOption(HealDoctor $doctor): array
    {
        return [
            'code' => $doctor->id,
            'name' => $doctor->person?->full_name,
            'email' => $doctor->person?->email,
            'telephone' => $doctor->person?->telephone,
            'specialty' => $doctor->specialty,
        ];
    }

    /**
     * Doctores que el usuario puede elegir: todos si administra agendas, y
     * unicamente el suyo cuando su cuenta esta limitada a su propia agenda.
     */
    protected function allowedDoctorOptions()
    {
        if (!$this->canChooseDoctor()) {
            $doctor = $this->currentDoctor();

            return $doctor ? [$this->doctorOption($doctor)] : [];
        }

        return HealDoctor::with('person')
            ->orderBy('id')
            ->get()
            ->map(fn (HealDoctor $doctor) => $this->doctorOption($doctor))
            ->values();
    }

    /** Opcion del doctor propio, null cuando el usuario no esta vinculado. */
    protected function currentDoctorOption(): ?array
    {
        $doctor = $this->currentDoctor();

        return $doctor ? $this->doctorOption($doctor) : null;
    }

    /** Opciones de paciente para los selectores de cita. */
    protected function patientOptions()
    {
        return HealPatient::with('person')
            ->orderBy('id')
            ->get()
            ->map(fn (HealPatient $patient) => [
                'code' => $patient->id,
                'name' => $patient->person?->full_name,
                'email' => $patient->person?->email,
                'telephone' => $patient->person?->telephone,
            ])
            ->values();
    }
}
