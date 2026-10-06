<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Support\ResolvesDoctorContext;

class CalendarController extends Controller
{
    use ResolvesDoctorContext;

    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        $canChooseDoctor = $this->canChooseDoctor();
        $currentDoctor = $this->currentDoctor();
        $canCreateAppointment = $canChooseDoctor || $currentDoctor !== null;

        return Inertia::render(
            'Calendar/Index',
            [
                'eventsDB' => $this->getDentAppointments($period, $canChooseDoctor, $currentDoctor),
                // La lista de pacientes solo la necesita el formulario de nueva cita.
                'patients' => $canCreateAppointment ? $this->patientOptions() : [],
                'doctors' => $this->allowedDoctorOptions(),
                'currentDoctor' => $this->currentDoctorOption(),
                'canChooseDoctor' => $canChooseDoctor,
            ]
        );
    }


    public function getDentAppointments($period, bool $canChooseDoctor = true, ?HealDoctor $currentDoctor = null)
    {
        // Obtener la fecha actual
        $now = Carbon::now();

        // Inicializar la consulta base
        $query = DentAppointment::query()->with(['patient', 'doctor']);

        // Quien no administra agendas solo ve las citas de su propia agenda.
        if (!$canChooseDoctor) {
            $query->where('doctor_id', $currentDoctor?->id ?? 0);
        }

        // Aplicar el filtro según el período
        if ($period === 'day') {
            $query->whereDate('date_appointmen', $now);
        } elseif ($period === 'week') {
            $query->whereBetween('date_appointmen', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereBetween('date_appointmen', [$now->copy()->startOfMonth()->format('Y-m-d'), $now->copy()->endOfMonth()->format('Y-m-d')]);
        }

        // Ejecutar la consulta y obtener los resultados
        return $query->get();
    }
}
