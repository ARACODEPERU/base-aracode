<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EventEdition;
use App\Models\Parameter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Modules\Socialevents\Entities\EventEditionAccordance;

class EventEditionAccordanceController extends Controller
{
    protected $P000010;

    public function __construct()
    {
        $this->P000010  = Parameter::where('parameter_code', 'P000010')->value('value_default');
    }

    public function index()
    {
        return view('socialevents::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id)
    {
        $edicion = EventEdition::find($id);
        $typeSessions = getEnumValues('event_edition_accordances','minutes_type');
        $tinyApiKey = $this->P000010;
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $documentTypes = DB::table('identity_document_type')->whereNotIn('id',['6'])->get();

        return Inertia::render('Socialevents::Editions/MinutesCreate',[
            'edicion' => $edicion,
            'tinyApiKey' => $tinyApiKey,
            'typeSessions' => $typeSessions,
            'ubigeo' => $ubigeo,
            'documentTypes' => $documentTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validación de campos
        $request->validate([
            'edition_id'      => 'required|exists:event_editions,id',
            'minutes_subject' => 'required|string|max:255',
            'minutes_type'    => 'required|in:delegados,extraordinaria,ordinaria,comite',
            'minutes_code'    => 'nullable|string|max:50',
            'meeting_date'    => 'required|date',
            'minutes_body'    => 'required|string',
            //'status'          => 'required|in:pending,open,accepted',
            // Validamos que sea un array y sus elementos internos
            'participants'    => 'nullable|array',
            'participants.*.person_id' => 'nullable',
            'participants.*.full_name' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request) {
                EventEditionAccordance::create([
                    'edition_id'      => $request->get('edition_id'),
                    'minutes_subject' => $request->get('minutes_subject'),
                    'minutes_type'    => $request->get('minutes_type'),
                    'meeting_date'    => $request->get('meeting_date'),
                    'participants'    => $request->get('participants'), // Se guarda como JSON gracias al cast
                    'minutes_body'    => $request->get('minutes_body'),
                    'status'          => $request->get('status'),
                    'user_by'         => Auth::id(),
                ]);
            });

            return to_route('even_ediciones_actas_listado', $request->get('edition_id'));

        } catch (\Exception $e) {
            return Redirect::back()->withErrors([
                'sql_errors' => 'Error al guardar el acta: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('socialevents::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $accordance = EventEditionAccordance::find($id);
        $edicion = EventEdition::find($accordance->edition_id);
        $typeSessions = getEnumValues('event_edition_accordances','minutes_type');
        $tinyApiKey = $this->P000010;
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $documentTypes = DB::table('identity_document_type')->whereNotIn('id',['6'])->get();

        return Inertia::render('Socialevents::Editions/MinutesEdit',[
            'edicion' => $edicion,
            'tinyApiKey' => $tinyApiKey,
            'typeSessions' => $typeSessions,
            'ubigeo' => $ubigeo,
            'documentTypes' => $documentTypes,
            'accordance' => $accordance
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'edition_id'      => 'required|exists:event_editions,id',
            'minutes_subject' => 'required|string|max:255',
            'minutes_type'    => 'required|in:delegados,extraordinaria,ordinaria,comite',
            'minutes_code'    => 'nullable|string|max:50',
            'meeting_date'    => 'required|date',
            'minutes_body'    => 'required|string',
            //'status'          => 'required|in:pending,open,accepted',
            // Validamos que sea un array y sus elementos internos
            'participants'    => 'nullable|array',
            'participants.*.person_id' => 'nullable',
            'participants.*.full_name' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $id = $request->get('id');
                EventEditionAccordance::find($id)->update([
                    'edition_id'      => $request->get('edition_id'),
                    'minutes_subject' => $request->get('minutes_subject'),
                    'minutes_type'    => $request->get('minutes_type'),
                    'meeting_date'    => $request->get('meeting_date'),
                    'participants'    => $request->get('participants'), // Se guarda como JSON gracias al cast
                    'minutes_body'    => $request->get('minutes_body'),
                    'status'          => $request->get('status'),
                    'user_by'         => Auth::id(),
                ]);
            });

            return to_route('even_ediciones_actas_listado', $request->get('edition_id'));

        } catch (\Exception $e) {
            return Redirect::back()->withErrors([
                'sql_errors' => 'Error al guardar el acta: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
