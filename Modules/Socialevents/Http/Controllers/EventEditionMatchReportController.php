<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Parameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAccordance;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Modules\SocialEvents\Services\PositionTableService;

class EventEditionMatchReportController extends Controller
{
    protected $positionService;
    protected $P000010;

    public function __construct()
    {
        $this->positionService = new PositionTableService();
        $this->P000010  = Parameter::where('parameter_code', 'P000010')->value('value_default');
    }

    public function editionMinutes($id)
    {
        $edicion = EventEdition::find($id);
        $minutesMatch = EventEditionMatchReport::with([
            'partido.equipolocal.manager',
            'partido.equipovisitante.manager',
            'partido.edicion.evento',
        ])->get();

        $newMinutesMatch = $minutesMatch->map(function ($minutes) {
            // 1. Convertimos a array para que acepte propiedades nuevas sin restricciones
            $m = $minutes->toArray();

            $m['minutes_subject'] = $minutes->partido->equipolocal->name .' VS '. $minutes->partido->equipovisitante->name;
            $m['minutes_type'] = 'partido';
            $m['url_edit'] = route('even_ediciones_pertido_actas_editar', $minutes->id);
            $m['participants'] = [
                [
                    'person_id' => $minutes->partido->equipolocal->manager?->id,
                    'full_name' => $minutes->partido->equipolocal->manager?->full_name,
                ],
                [
                    'person_id' => $minutes->partido->equipovisitante->manager?->id,
                    'full_name' => $minutes->partido->equipovisitante->manager?->full_name,
                ],
            ];

            $m['referees'] = $minutes->referees;
            $m['status'] = $minutes->status;
            $m['minutes_file_path'] = $minutes->minutes_file_path;
            $m['minutes_file_name'] = $minutes->minutes_file_name;
            $m['minutes_subject_local'] = $minutes->partido->equipolocal->name;
            $m['minutes_subject_visitor'] = $minutes->partido->equipovisitante->name;
            $m['edition_name'] = $minutes->partido->edicion->name;
            $m['event_name'] = $minutes->partido->edicion->evento->title;
            $m['meeting_date'] = $minutes->created_at;

            return $m;
        });

        $minutesGenerals = EventEditionAccordance::with([
            'edicion.evento',
        ])->get();

        $newMinutesGenerals = $minutesGenerals->map(function ($minutes) {
            // 1. Convertimos a array para que acepte propiedades nuevas sin restricciones
            $m = $minutes->toArray();

            $m['minutes_subject'] = $minutes->minutes_subject;
            $m['minutes_type'] = $minutes->minutes_type;
            $m['url_edit'] = route('even_ediciones_actas_editar', $minutes->id);
            $m['participants'] = $minutes->participants;

            $m['status'] = $minutes->status;
            $m['minutes_file_path'] = $minutes->minutes_file_path;
            $m['minutes_file_name'] = $minutes->minutes_file_name;
            $m['minutes_subject_local'] = null;
            $m['minutes_subject_visitor'] = null;
            $m['edition_name'] = $minutes->edicion->name;
            $m['event_name'] = $minutes->edicion->evento->title;
            $m['meeting_date'] = $minutes->meeting_date;
            return $m;
        });

        // 1. Unimos las dos colecciones mapeadas
        $mergedMinutes = $newMinutesMatch->concat($newMinutesGenerals);

        // 2. Ordenamos por la fecha que igualaste (meeting_date)
        // Usamos values() para resetear los índices y evitar problemas con el JSON en el frontend
        $sortedMinutes = $mergedMinutes->sortByDesc(function ($item) {
            // Nos aseguramos de convertirlo a timestamp para un ordenamiento preciso
            return strtotime($item['meeting_date']);
        })->values();

        return Inertia::render('Socialevents::Editions/Minutes',[
            'edicion' => $edicion,
            'sortedMinutes' => $sortedMinutes
        ]);
    }


    public function generateTemporaryPdf(Request $request)
    {
        $acta = $request->get('acta');
        //dd($acta);
        $type = $acta['minutes_type'];

        if($type == 'partido'){
            $pdf = Pdf::loadView('socialevents::minutes.pdf.minutes_match', [
                'acta' => $acta
            ]);
        }else{
            $pdf = Pdf::loadView('socialevents::minutes.pdf.minutes_match', $acta);
        }


        $directory = 'temp_pdfs';

        // 1. Limpiar la carpeta por completo antes de guardar el nuevo
        // Esto elimina todos los archivos y la carpeta misma
        Storage::disk('public')->deleteDirectory($directory);

        // 2. Volver a crear la carpeta (ahora está vacía)
        Storage::disk('public')->makeDirectory($directory);

        // 3. Definir el nombre y la ruta
        $fileName = 'temp_acta_' . date('Ymd') . $acta['id'] . '.pdf';
        $filePath = $directory . '/' . $fileName;

        // 4. Guardar el archivo nuevo
        Storage::disk('public')->put($filePath, $pdf->output());

        // 5. Devolver la URL pública
        return response()->json([
            'url' => asset('storage/' . $filePath)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function updateReportSolution(Request $request)
    {
        // dd($request->all());
        $this->validate($request, [
            'resolution_details' => 'required|string'
        ]);

        $id = $request->get('id');
        $edition_id = $request->get('edition_id');
        $match_id = $request->get('match_id');
        $shouldChangeScore = $request->boolean('change_score');

        EventEditionMatchReport::find($id)
            ->update([
                'protest_status' => 'pending',
                'resolution_details' => $request->get('resolution_details')
            ]);

        if ($shouldChangeScore) {
            EventEditionMatch::find($match_id)->update([
                'score_h' => $request->new_score_h,
                'score_a' => $request->new_score_a
            ]);
        }

        $this->positionService->updateTablePositions($edition_id);
    }

    public function updateReportFile(Request $request)
    {
        // 1. Validación inicial
        $request->validate([
            'acta_id' => 'required|exists:event_edition_match_reports,id',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $report = EventEditionMatchReport::findOrFail($request->acta_id);
                $match = EventEditionMatch::findOrFail($report->match_id);

                if ($match->status === 'closed') {
                    // Usamos ValidationException para que sea nativo de los formularios
                    throw ValidationException::withMessages([
                        'file' => 'Este partido ya está cerrado y no admite más cambios.'
                    ]);
                }

                $path = $request->file('file')->store('uploads/eventos/actas_firmadas', 'public');

                $report->update([
                    'minutes_file_path' => $path,
                    'minutes_file_name' => $request->file('file')->getClientOriginalName(),
                ]);

                $match->update(['status' => 'closed']);
            });

            return Redirect::back();

        } catch (ValidationException $e) {
            // Dejar que Laravel maneje las excepciones de validación automáticamente
            throw $e;
        } catch (\Exception $e) {
            // Para cualquier otro error técnico (BD, File System), forzamos un string
            // Evitamos que $e sea tratado como objeto/array
            $errorMessage = (string) $e->getMessage();

            return Redirect::back()->withErrors([
                'file' => 'Error técnico: ' . $errorMessage
            ]);
        }
    }

    public function edit($id){

        $accordance = EventEditionMatchReport::with([
            'partido.equipolocal.manager',
            'partido.equipovisitante.manager',
            'partido.edicion.evento',
        ])->where('id', $id)->first();
            //dd($accordance);
        $edicion = EventEdition::find($accordance->partido->edition_id);
        $resolutionStatus = getEnumValues('event_edition_match_reports','protest_status');
        $tinyApiKey = $this->P000010;
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $documentTypes = DB::table('identity_document_type')->whereNotIn('id',['6'])->get();

        return Inertia::render('Socialevents::Editions/MinutesMatchEdit', [
            'edicion' => $edicion,
            'tinyApiKey' => $tinyApiKey,
            'resolutionStatus' => $resolutionStatus,
            'ubigeo' => $ubigeo,
            'documentTypes' => $documentTypes,
            'accordance' => $accordance
        ]);
    }
    public function destroy($id)
    {
        //
    }
}
