<?php

namespace Modules\Dental\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use DataTables;
use Modules\Dental\Entities\DentAttention;
use Modules\Health\Entities\HealDoctor;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Entities\HealHistory;
use Modules\Health\Entities\HealPatient;

class DentAttentionController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Dental::Attentions/List');
    }

    public function getTable()
    {

        $table = (new DentAttention())->newQuery();
        return DataTables::of($table)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $doctors = HealDoctor::with('person')->get();
        if (count($doctors) > 0) {
            foreach ($doctors as $i => $doctor) {
                $doctors[$i] = array(
                    'code' => $doctor->id,
                    'name' => $doctor->person->full_name,
                    'email' => $doctor->person->email,
                    'telephone' => $doctor->person->telephone
                );
            }
        }

        return Inertia::render('Dental::Attentions/Create', [
            'doctors' => $doctors
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'date_time_attention'   => 'required',
                'current_illness'       => 'required|max:300',
                'patient_id'            => 'required',
                'doctor_id'             => 'required',
                'treatment'             => 'required'
            ]
        );

        $doctor = HealDoctor::find($request->get('doctor_id')['code']);
        $patient = HealPatient::with('person')->where('id', $request->get('patient_id'))->first();

        $history = HealHistory::firstOrCreate(
            ['patient_id' => $request->get('patient_id')],
            ['history_code' => $patient->person->number]
        );

        if ($history) {

            $attention = DentAttention::create([
                'date_time_attention' => $request->get('date_time_attention'),
                'current_illness' => $request->get('current_illness') ?? null,
                'reason_consultation' => $request->get('reason_consultation') ?? null,
                'age' => $request->get('age') ?? null,
                'sick_time' => $request->get('sick_time') ?? null,
                'appetite' => $request->get('appetite') ?? null,
                'thirst' => $request->get('thirst') ?? null,
                'dream' => $request->get('dream') ?? null,
                'mood' => $request->get('mood') ?? null,
                'urine' => $request->get('urine') ?? null,
                'depositions' => $request->get('depositions') ?? null,
                'weight_loss' => $request->get('weight_loss') ?? null,
                'pex_tem' => $request->get('pex_tem') ?? null,
                'pex_pa' => $request->get('pex_pa') ?? null,
                'pex_fc' => $request->get('pex_fc') ?? null,
                'pex_fr' => $request->get('pex_fr') ?? null,
                'pex_peso' => $request->get('pex_peso') ?? null,
                'pex_talla' => $request->get('pex_talla') ?? null,
                'pex_imc' => $request->get('pex_imc') ?? null,
                'treatment' => $request->get('treatment') ?? null,
                'pex_aux_examination' => json_encode($request->get('pex_aux_examination')),
                'doctor_id' => $doctor->id,
                'user_id' => Auth::id(),
                'patient_id' => $request->get('patient_id'),
                'appointment_id' => $request->get('appointment_id') ?? null,
                'signed_accepted' => $request->get('signed_accepted') ? true : false,
                'observations' => $request->get('observations'),
                'history_id' => $history->id
            ]);

            $ndi = $request->get('next_appointmen_doctor_id')['code'];
            $nda = $request->get('next_date_appointment');
            $nta = $request->get('next_time_appointment');

            if ($ndi && $nda && $nta) {

                $ddate = $request->get('next_date_appointment') . ' ' . $request->get('next_time_appointment');
                $initialDateTime = Carbon::parse($ddate);
                $newDateTime = $initialDateTime->addMinutes(30);

                $appointment = DentAppointment::create([
                    'patient_id'            => $patient->id,
                    'patient_person_id'     => $patient->person_id,
                    'doctor_id'             => $doctor->id,
                    'doctor_person_id'      => $doctor->person_id,
                    'date_appointmen'       => $request->get('next_date_appointment'),
                    'time_appointmen'       => $request->get('next_time_appointment'),
                    'date_end_appointmen'   => $newDateTime->toDateString(),
                    'time_end_appointmen'   => $newDateTime->toTimeString(),
                    'email'                 => $patient->person->email,
                    'telephone'             => $patient->person->telephone,
                    'description'           => $request->get('current_illness') ?? null,
                    'details'               => $request->get('reason_consultation') ?? null,
                    'message'               => null,
                    'status'                => 1,
                    'created_user_id'       => Auth::id(),
                    'updated_user_id'       => Auth::id(),
                    'sick_time'             => $request->get('sick_time') ?? null,
                ]);

                $attention->next_appointment_id = $appointment->id;
                $attention->save();
            }
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('dental::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('dental::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
