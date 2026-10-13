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
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Socialevents\Services\SportsOrganizationService;
use Illuminate\Support\Facades\Storage;

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
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite(request()->user());
        $sports->assertOwnedEdition(request()->user(), (int) $id);
        $edicion = EventEdition::find($id);
        $typeSessions = getEnumValues('event_edition_accordances','minutes_type');
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
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
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

        $sports->assertOwnedEdition($request->user(), (int) $request->input('edition_id'));
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
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite(request()->user());
        $accordance = EventEditionAccordance::findOrFail($id);
        $sports->assertOwnedEdition(request()->user(), (int) $accordance->edition_id);
        $edicion = EventEdition::find($accordance->edition_id);
        $typeSessions = getEnumValues('event_edition_accordances','minutes_type');
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
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
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
                $accordance = EventEditionAccordance::findOrFail($id);
                app(SportsOrganizationService::class)->assertOwnedEdition($request->user(), (int) $accordance->edition_id);
                app(SportsOrganizationService::class)->assertOwnedEdition($request->user(), (int) $request->input('edition_id'));
                $accordance->update([
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

    public function generateAccordancePdf(Request $request)
    {
        $acta = $request->get('acta');
        $accordance = EventEditionAccordance::findOrFail((int) ($acta['id'] ?? 0));
        app(SportsOrganizationService::class)->assertOwnedEdition($request->user(), (int) $accordance->edition_id);
        //dd($acta);
        $pdf = Pdf::loadView('socialevents::minutes.pdf.minutes_accordance', [
            'acta' => $acta
        ]);

        $directory = 'temp_pdfs';

        // 1. Limpiar la carpeta por completo antes de guardar el nuevo
        Storage::disk('public')->deleteDirectory($directory);

        // 2. Volver a crear la carpeta (ahora está vacía)
        Storage::disk('public')->makeDirectory($directory);

        // 3. Definir el nombre y la ruta
        $fileName = 'temp_accordance_' . date('Ymd') . $acta['id'] . '.pdf';
        $filePath = $directory . '/' . $fileName;

        // 4. Guardar el archivo nuevo
        Storage::disk('public')->put($filePath, $pdf->output());

        // 5. Devolver la URL pública
        return response()->json([
            'url' => asset('storage/' . $filePath)
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
