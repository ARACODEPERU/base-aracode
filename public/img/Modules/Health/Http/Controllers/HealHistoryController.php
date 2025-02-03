<?php

namespace Modules\Health\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Dental\Entities\DentAttention;
use Modules\Health\Entities\HealAllergy;
use Modules\Health\Entities\HealPatient;

class HealHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('health::index');
    }

    public function patientStory($id)
    {
        $patient = HealPatient::with('person')
            ->where('id', $id)
            ->first();

        $allergies = $allergies = HealAllergy::with(['allergyPatient' => function ($query) use ($id) {
            $query->where('patient_id', $id);
        }])->get();

        return Inertia::render('Health::History/PatientStory', [
            'patient' => $patient,
            'allergies' => $allergies
        ]);
    }

    public function lastVitalSigns($id)
    {
        ///ultimos estudios odontologicos

        $vitals = DentAttention::where('patient_id', $id)
            ->orderByDesc('date_time_attention')
            ->first();
        //dd($vitals);
        return response()->json([
            'vitals' => $vitals
        ]);
    }
}
