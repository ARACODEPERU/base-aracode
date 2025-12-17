<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventTeam;

class EventEditionTeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $teams = EventTeam::get();

        $urrentEquipment = EventEditionTeam::where('edition_id', $id)->get();

        $edicion = EventEdition::find($id);

        return Inertia::render('Socialevents::Editions/Teams', [
            'teams' => $teams,
            'urrentEquipment' => $urrentEquipment,
            'edicion' => $edicion
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('socialevents::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
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
