<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EvenLocal;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Services\TournamentService;

class EventEditionMatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function editionFixtures($id)
    {
        $edition = EventEdition::find($id);
        $teams = EventEditionTeam::with('equipo')->where('edition_id', $id)->get();
        $locales = EvenLocal::where('status', true)->get();

        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante']) // Carga las relaciones
            ->where('edition_id', $id)
            ->orderByRaw("
                CASE
                    -- 1. Partidos sin fecha van primero (o según tu preferencia)
                    WHEN match_date IS NULL THEN 0
                    -- 2. Partidos que están por jugarse o jugándose (fecha futura o hoy)
                    WHEN match_date >= NOW() THEN 1
                    -- 3. Partidos ya finalizados
                    ELSE 2
                END ASC
            ")
            // Dentro de cada grupo anterior, ordenamos por fecha
            // Los futuros más cercanos primero, los pasados más recientes primero
            ->orderByRaw("ABS(TIMESTAMPDIFF(SECOND, NOW(), match_date)) ASC")
            ->get()
            ->groupBy('phase');
        //dd($matches);
        return Inertia::render('Socialevents::Editions/Fixtures',[
            'teams' => $teams,
            'edition' => $edition,
            'fixture' => $matches,
            'locales' => $locales
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function editionFixturesCreate($id)
    {
        $edition = EventEdition::find($id);
        $teams = EventTeam::where('status', true)->get();
        $locales = EvenLocal::where('status', true)->get();

        return Inertia::render('Socialevents::Fixtures/Create',[
            'edition' => $edition,
            'teams' => $teams,
            'locales' => $locales
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function editionFixturesGenerate(Request $request, $id)
    {
        $request->validate([
            'teams' => 'required|array|min:2',
            'format' => 'required|string',
        ], [
            'teams.required' => 'No se han seleccionado equipos para generar el torneo.',
            'teams.min' => 'Necesitas al menos 2 equipos.'
        ]);
        $service = new TournamentService();
        // Si pasa la validación, entonces llamas a tu método
        $service->generate($request->get('edition_id'), $request->get('format'), $request->get('teams'));
    }

    /**
     * Show the specified resource.
     */
    public function editionFixturesStore()
    {

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
    public function editionFixturesUpdate(Request $request, $id)
    {
        $request->validate([
            'round_number' => 'required|max:2',
            //'group_name' => 'required|string',
            'match_date' => 'required|string',
            //'location' => 'required|string',
        ]);

        EventEditionMatch::find($id)
            ->update([
                'round_number' => $request->get('round_number'),
                'group_name' => $request->get('group_name') ?? null,
                'match_date' => $request->get('match_date'),
                'location' => $request->get('location') ?? null
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
