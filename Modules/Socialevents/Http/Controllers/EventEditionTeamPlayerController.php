<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Person;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Services\SportsOrganizationService;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
class EventEditionTeamPlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('socialevents::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function teamPlayersCreate($eId, $tId)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead(request()->user());
        $sports->assertOwnedEditionTeam(request()->user(), (int) $eId, (int) $tId);
        $edicion = EventEdition::findOrFail($eId);
        $equipo = EventTeam::findOrFail($tId);
        $players = EventEditionTeamPlayer::with('person')->where('edition_id', $eId)->where('team_id',$tId)->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $documentTypes = DB::table('identity_document_type')->whereNotIn('id',['6'])->get();

        return Inertia::render('Socialevents::Teams/Players',[
            'edicion' => $edicion,
            'equipo' => $equipo,
            'ubigeo' => $ubigeo,
            'documentTypes' => $documentTypes,
            'players' => $players
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function teamPlayersStore(Request $request)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $sports->assertOwnedEditionTeam($request->user(), (int) $request->input('edition_id'), (int) $request->input('team_id'));
        $this->validate(
            $request,
            [
                'team_id' => 'required',
                'edition_id' => 'required',
                'player_id' => [
                    'required',
                    Rule::unique('event_edition_team_players', 'person_id')
                        ->where(fn ($query) =>
                            $query->where('edition_id', $request->edition_id)
                        ),
                ],
            ],
            [
                'team_id.required' => 'El equipo es obligatorio.',
                'edition_id.required' => 'La edición es obligatoria.',
                'player_id.required' => 'El jugador es obligatorio.',
                'player_id.unique' => 'El jugador ya está registrado en otro equipo para esta edición.',
            ]
        );

        EventEditionTeamPlayer::create([
            'edition_id' => $request->get('edition_id'),
            'team_id' => $request->get('team_id'),
            'person_id' => $request->get('player_id'),
            'registration_date' => Carbon::now()->format('Y-m-d')
        ]);

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
        return view('socialevents::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function teamPlayersUpdate(Request $request)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $sports->assertOwnedEditionTeam($request->user(), (int) $request->input('edition_id'), (int) $request->input('team_id'));
        $this->validate(
            $request,
            [
                'team_id' => 'required',
                'edition_id' => 'required',
                'player_id' => 'required',
            ],
            [
                'team_id.required' => 'El equipo es obligatorio.',
                'edition_id.required' => 'La edición es obligatoria.',
                'player_id.required' => 'El jugador es obligatorio.',
            ]
        );

        if($request->get('type') == 1){
            EventEditionTeamPlayer::where('edition_id', $request->get('edition_id'))
                ->where('team_id', $request->get('team_id'))
                ->where('person_id', $request->get('player_id'))
                ->update([
                'jersey_number' => $request->get('valor')
            ]);
        }
        if($request->get('type') == 2){
            EventEditionTeamPlayer::where('edition_id', $request->get('edition_id'))
                ->where('team_id', $request->get('team_id'))
                ->where('person_id', $request->get('player_id'))
                ->update([
                'position' => $request->get('valor')
            ]);
        }
        if($request->get('type') == 3){
            EventEditionTeamPlayer::where('edition_id', $request->get('edition_id'))
                ->where('team_id', $request->get('team_id'))
                ->where('person_id', $request->get('player_id'))
                ->update([
                'role_in_team' => $request->get('valor')
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function teamPlayersDestroy($eId, $tId, $pId)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite(request()->user());
        $sports->assertOwnedEditionTeam(request()->user(), (int) $eId, (int) $tId);
        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = EventEditionTeamPlayer::where('edition_id', $eId)
                ->where('team_id', $tId)
                ->where('person_id', $pId)
                ->firstOrFail();

            if($item){
                EventEditionTeamPlayer::where('edition_id', $eId)
                    ->where('team_id', $tId)
                    ->where('person_id', $pId)
                    ->delete();
            }

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Eliminado correctamente';
            $success = true;
        } catch (\Exception $e) {
            // Si ocurre alguna excepción durante la transacción, hacemos rollback para deshacer cualquier cambio.
            DB::rollback();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }

    /**
     * Actualiza los datos personales de un jugador (nombres, documento, etc.) y, opcionalmente, su foto.
     */
    public function teamPlayerPersonUpdate(Request $request)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $editionId = (int) $request->input('edition_id');
        $teamId = (int) $request->input('team_id');
        $sports->assertOwnedEditionTeam($request->user(), $editionId, $teamId);

        $this->validate(
            $request,
            [
                'edition_id' => 'required',
                'team_id' => 'required',
                'player_id' => 'required|exists:people,id',
                'names' => 'required|string|max:255',
                'father_lastname' => 'required|string|max:255',
                'mother_lastname' => 'nullable|string|max:255',
                'document_type_id' => 'required|exists:identity_document_type,id',
                'number' => 'required|string|max:20',
                'birthdate' => 'nullable|date',
                'image' => 'nullable|string',
            ],
            [
                'names.required' => 'Los nombres son obligatorios.',
                'father_lastname.required' => 'El apellido paterno es obligatorio.',
                'document_type_id.required' => 'El tipo de documento es obligatorio.',
                'document_type_id.exists' => 'El tipo de documento no es válido.',
                'number.required' => 'El número de documento es obligatorio.',
                'birthdate.date' => 'La fecha de nacimiento no es válida.',
            ]
        );

        // El jugador debe pertenecer al equipo y edición indicados.
        abort_unless(EventEditionTeamPlayer::where('edition_id', $editionId)->where('team_id', $teamId)->where('person_id', $request->player_id)->exists(), 404);
        $person = Person::findOrFail($request->player_id);

        $names = trim($request->names);
        $fatherLastname = trim($request->father_lastname);
        $motherLastname = trim((string) $request->mother_lastname);

        $person->update([
            'names' => $names,
            'father_lastname' => $fatherLastname,
            'mother_lastname' => $motherLastname,
            'full_name' => trim(implode(' ', array_filter([$names, $fatherLastname, $motherLastname]))),
            'document_type_id' => $request->document_type_id,
            'number' => trim($request->number),
            'birthdate' => $request->birthdate ?: null,
        ]);

        // La foto es opcional: solo se procesa si se envió una imagen nueva.
        if ($request->filled('image')) {
            $this->storePlayerImage($person, $request->get('image'));
        }

        // Inertia exige una respuesta de redirección; un JSON plano hace que el frontend no complete la petición.
        return back()->with('success', 'Jugador actualizado correctamente');
    }

    /**
     * Genera una tarjeta de credencial (fotochet) en PDF para un jugador del equipo.
     */
    public function printPlayerCard($editionId, $teamId, $playerId)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead(request()->user());
        $sports->assertOwnedEditionTeam(request()->user(), (int) $editionId, (int) $teamId);

        // El jugador debe pertenecer al equipo y edición indicados.
        $player = EventEditionTeamPlayer::with('person')
            ->where('edition_id', $editionId)
            ->where('team_id', $teamId)
            ->where('person_id', $playerId)
            ->firstOrFail();

        $edition = EventEdition::with('evento')->findOrFail($editionId);
        $team = EventTeam::findOrFail($teamId);

        $pdf = Pdf::loadView('socialevents::teams.pdf.player_card', [
            'data' => $this->datosTarjetaJugador($player->person, $edition, $team)
        ])->setPaper([0, 0, 243, 340]);

        $fileName = 'fotochet_' . $playerId . '_' . date('YmdHis') . '.pdf';

        return response()->json([
            'url' => $this->guardarPdfTemporal($pdf, $fileName)
        ]);
    }

    /**
     * Genera un PDF A4 con varias tarjetas (fotochets) de los jugadores seleccionados del equipo.
     */
    public function printPlayerCardsA4(Request $request, $editionId, $teamId)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead($request->user());
        $sports->assertOwnedEditionTeam($request->user(), (int) $editionId, (int) $teamId);

        $request->validate([
            'player_ids'   => 'required|array|min:1',
            'player_ids.*' => 'required|integer',
        ], [
            'player_ids.required' => 'Selecciona al menos un jugador.',
            'player_ids.min'      => 'Selecciona al menos un jugador.',
        ]);

        // Solo se imprimen jugadores que pertenecen al equipo y edición indicados.
        $players = EventEditionTeamPlayer::with('person')
            ->where('edition_id', $editionId)
            ->where('team_id', $teamId)
            ->whereIn('person_id', $request->input('player_ids'))
            ->get();

        if ($players->isEmpty()) {
            return response()->json(['message' => 'No se encontraron jugadores para imprimir.'], 422);
        }

        $edition = EventEdition::with('evento')->findOrFail($editionId);
        $team = EventTeam::findOrFail($teamId);

        $cards = $players->map(fn ($player) => $this->datosTarjetaJugador($player->person, $edition, $team))->values()->all();

        $pdf = Pdf::loadView('socialevents::teams.pdf.player_cards_a4', [
            'cards' => $cards
        ])->setPaper('a4', 'portrait');

        $fileName = 'fotochets_equipo_' . $teamId . '_' . date('YmdHis') . '.pdf';

        return response()->json([
            'url' => $this->guardarPdfTemporal($pdf, $fileName)
        ]);
    }

    /**
     * Arma los datos de una tarjeta; la foto se incrusta en base64 para que DomPDF la renderice sin URLs externas.
     */
    private function datosTarjetaJugador(Person $person, EventEdition $edition, EventTeam $team): array
    {
        $photo = null;
        if ($person->image && Storage::disk('public')->exists($person->image)) {
            $mime = Storage::disk('public')->mimeType($person->image);
            $photo = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($person->image));
        }

        return [
            'team_name'    => $team->name,
            'event_name'   => $edition->evento->title,
            'edition_name' => $edition->name,
            'names'        => $person->names,
            'last_names'   => trim($person->father_lastname . ' ' . $person->mother_lastname),
            'photo'        => $photo,
        ];
    }

    /**
     * Guarda el PDF en temp_pdfs y devuelve su URL pública.
     */
    private function guardarPdfTemporal($pdf, string $fileName): string
    {
        Storage::disk('public')->makeDirectory('temp_pdfs');
        $filePath = 'temp_pdfs/' . $fileName;
        Storage::disk('public')->put($filePath, $pdf->output());

        return asset('storage/' . $filePath);
    }

    /**
     * Guarda una imagen base64 como foto del jugador, reemplazando la anterior.
     */
    private function storePlayerImage(Person $person, string $base64Image): void
    {
        if ($person->image && Storage::disk('public')->exists($person->image)) {
            Storage::disk('public')->delete($person->image);
        }

        $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
        $tempFile = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tempFile, $fileData);
        $mime = mime_content_type($tempFile);

        $name = uniqid('', true) . '.' . str_replace('image/', '', $mime);
        $file = new UploadedFile(realpath($tempFile), $name, $mime, null, true);

        $file_name = time() . rand(100, 999) . '.' . $file->getClientOriginalExtension();
        $path = Storage::disk('public')->putFileAs('players', $file, $file_name);
        $person->update(['image' => $path]);
    }

    public function teamPlayerImageUpdate(Request $request)
    {
        $this->validate(
            $request,
            [
                'player_id' => 'required|exists:people,id',
                'image' => 'required|string',
            ],
            [
                'player_id.required' => 'El jugador es obligatorio.',
                'player_id.exists' => 'El jugador no existe.',
                'image.required' => 'La imagen es obligatoria.',
                'image.string' => 'La imagen debe ser una cadena base64.',
            ]
        );

        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $editionId = (int) $request->input('edition_id');
        $teamId = (int) $request->input('team_id');
        $sports->assertOwnedEditionTeam($request->user(), $editionId, $teamId);
        abort_unless(EventEditionTeamPlayer::where('edition_id', $editionId)->where('team_id', $teamId)->where('person_id', $request->player_id)->exists(), 404);
        $person = Person::findOrFail($request->player_id);

        $base64Image = $request->get('image');

        if ($base64Image) {
            // Eliminar imagen anterior si existe
            if ($person->image && Storage::disk('public')->exists($person->image)) {
                Storage::disk('public')->delete($person->image);
            }

            // Procesar base64
            $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            if (PHP_OS == 'WINNT') {
                $tempFile = tempnam(sys_get_temp_dir(), 'img');
            } else {
                $tempFile = tempnam('/tmp', 'img');
            }
            file_put_contents($tempFile, $fileData);
            $mime = mime_content_type($tempFile);

            $name = uniqid('', true) . '.' . str_replace('image/', '', $mime);
            $file = new UploadedFile(realpath($tempFile), $name, $mime, null, true);

            if ($file) {
                $destination = 'players';
                $file_name = time() . rand(100, 999) . '.' . $file->getClientOriginalExtension();
                $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                $person->update(['image' => $path]);
            }
        }
    }
}
