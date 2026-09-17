<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Parameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAccordance;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchReport;
use Modules\Socialevents\Entities\EventEditionPointAdjustment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Modules\Socialevents\Services\PositionTableService;

class EventEditionMatchReportController extends Controller
{
    protected $positionService;
    protected $P000010;

    /** Máximo de evidencias (imágenes + PDF) por reclamo. */
    const MAX_PROTEST_FILES = 4;

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

            // Sanción administrativa ya registrada en esta acta (para precargar el
            // formulario de resolución al reabrir el acuerdo).
            $existingSanction = EventEditionPointAdjustment::where('report_id', $minutes->id)->get();
            $sanctionedAdjustment = $existingSanction->firstWhere('points', '<', 0);
            $m['sanction'] = [
                'apply_sanction' => $existingSanction->isNotEmpty(),
                'sanctioned_team_id' => (int) ($sanctionedAdjustment->team_id ?? 0),
                'sanction_points' => $sanctionedAdjustment ? (int) abs((int) $sanctionedAdjustment->points) : 3,
            ];

            // Evidencias del reclamo con URL publica para previsualizar/descargar.
            $m['protest_files'] = collect($minutes->protest_files ?? [])->map(fn ($f) => [
                'name' => $f['name'] ?? 'archivo',
                'path' => $f['path'] ?? '',
                'url' => isset($f['path']) ? asset('storage/'.$f['path']) : '',
            ])->values()->all();

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
        $shouldApplySanction = $request->boolean('apply_sanction');

        // Evidencias del reclamo (imagenes o PDF) — opcionales, máx. 4 en total.
        $this->validate($request, [
            'protest_files' => 'nullable|array|max:'.self::MAX_PROTEST_FILES,
            'protest_files.*' => 'file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        try {
            DB::transaction(function () use ($request, $id, $edition_id, $match_id, $shouldChangeScore, $shouldApplySanction) {
                $report = EventEditionMatchReport::find($id);

                $report->update([
                    // Si el acta tiene reclamo se marca como resuelto; si no tiene
                    // (p. ej. partido ya cerrado sin reclamo), se conserva su estado.
                    'protest_status' => $report->has_protest ? 'resolved' : $report->protest_status,
                    'resolution_details' => $request->get('resolution_details'),
                    'points_processed' => true,
                ]);

                // Adjuntar las pruebas presentadas (se acumulan con las existentes).
                // El archivo se guarda en el disco del servidor (storage/app/public)
                // y en la BD solo se registra la ruta, igual que las demás cargas.
                if ($request->hasFile('protest_files')) {
                    $existing = $report->protest_files ?? [];
                    $newFiles = $request->file('protest_files');

                    if (count($existing) + count($newFiles) > self::MAX_PROTEST_FILES) {
                        throw ValidationException::withMessages([
                            'protest_files' => 'Solo se permiten hasta '.self::MAX_PROTEST_FILES.' evidencias en total (entre imágenes y PDF).',
                        ]);
                    }

                    foreach ($newFiles as $file) {
                        $path = $file->store('uploads/eventos/reclamos', 'public');
                        $existing[] = [
                            'name' => $file->getClientOriginalName(),
                            'path' => $path,
                        ];
                    }
                    $report->update(['protest_files' => $existing]);
                }

                if ($shouldChangeScore) {
                    EventEditionMatch::find($match_id)->update([
                        'score_h' => $request->new_score_h,
                        'score_a' => $request->new_score_a
                    ]);
                }

                // Sanción administrativa de puntos: el resultado deportivo del partido
                // se mantiene; solo se ajustan los puntos de la tabla oficial.
                // Se eliminan los ajustes previos de este acta para que reenviar no duplique.
                EventEditionPointAdjustment::where('report_id', $id)->delete();

                if ($shouldApplySanction) {
                    $sanctionedTeamId = (int) $request->get('sanctioned_team_id');
                    $sanctionPoints = (int) $request->get('sanction_points');
                    $match = EventEditionMatch::find($match_id);

                    if (! in_array($sanctionedTeamId, [(int) $match->team_h_id, (int) $match->team_a_id], true)) {
                        throw ValidationException::withMessages([
                            'sanctioned_team_id' => 'El equipo sancionado debe ser uno de los equipos del partido.',
                        ]);
                    }
                    if ($sanctionPoints < 1 || $sanctionPoints > 99) {
                        throw ValidationException::withMessages([
                            'sanction_points' => 'Los puntos de la sanción deben estar entre 1 y 99.',
                        ]);
                    }

                    $reason = $request->get('sanction_reason') ?: 'Sanción administrativa por resolución de comisión';

                    // Equipo sancionado: pierde los puntos (registro negativo).
                    EventEditionPointAdjustment::create([
                        'edition_id' => $edition_id,
                        'team_id' => $sanctionedTeamId,
                        'match_id' => $match_id,
                        'report_id' => $id,
                        'points' => -$sanctionPoints,
                        'reason' => $reason,
                        'created_by' => Auth::id(),
                    ]);

                    // El rival recibe los puntos otorgados por el reclamo procedente.
                    $rivalTeamId = $sanctionedTeamId === (int) $match->team_h_id
                        ? (int) $match->team_a_id
                        : (int) $match->team_h_id;

                    EventEditionPointAdjustment::create([
                        'edition_id' => $edition_id,
                        'team_id' => $rivalTeamId,
                        'match_id' => $match_id,
                        'report_id' => $id,
                        'points' => $sanctionPoints,
                        'reason' => 'Reclamo procedente: puntos otorgados por resolución de comisión',
                        'created_by' => Auth::id(),
                    ]);
                }

                $this->positionService->updateTablePositions($edition_id);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return Redirect::back()->withErrors([
                'resolution_details' => 'Error al registrar la resolución: '.$e->getMessage(),
            ], 'saveSolution');
        }

        return Redirect::back()->with('success', 'Resolución registrada y tabla de posiciones actualizada');
    }

    /**
     * Elimina una evidencia del reclamo (archivo fisico + entrada JSON).
     */
    public function destroyProtestFile(Request $request)
    {
        $validated = $request->validate([
            'report_id' => 'required|integer',
            'index' => 'required|integer',
        ]);

        $report = EventEditionMatchReport::findOrFail($validated['report_id']);
        $files = $report->protest_files ?? [];
        $index = (int) $validated['index'];

        if (! isset($files[$index])) {
            return response()->json(['success' => false, 'message' => 'El archivo no existe.'], 404);
        }

        if (! empty($files[$index]['path'])) {
            Storage::disk('public')->delete($files[$index]['path']);
        }

        unset($files[$index]);
        $report->update(['protest_files' => array_values($files)]);

        return response()->json(['success' => true, 'message' => 'Evidencia eliminada correctamente']);
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
            'resolutionStatus' => $resolutionStatus,
            'ubigeo' => $ubigeo,
            'documentTypes' => $documentTypes,
            'accordance' => $accordance
        ]);
    }
    public function update(Request $request)
    {

        $id = $request->get('id');
        $report = EventEditionMatchReport::findOrFail($id);

        $report->update([
            'observations' => $request->get('observations'),
            'protest_status' => $request->get('protest_status'),
            'protest_description' => $request->get('protest_description'),
            'resolution_status' => $request->get('resolution_status'),
            'resolution_description' => $request->get('resolution_description'),
            'status' => $request->get('status')
        ]);

        return Redirect::back()->with('success', 'Actualizado correctamente');
    }

    public function destroy($id)
    {
        //
    }
}
