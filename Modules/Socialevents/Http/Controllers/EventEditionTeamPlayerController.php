<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Illuminate\Validation\Rule;
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
        $edicion = EventEdition::find($eId);
        $equipo = EventTeam::find($tId);
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
}
