<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

        $urrentEquipment = EventEditionTeam::with('equipo')->where('edition_id', $id)->get();

        $edicion = EventEdition::find($id);

        return Inertia::render('Socialevents::Editions/Teams', [
            'teams' => $teams,
            'urrentEquipment' => $urrentEquipment,
            'edicion' => $edicion
        ]);
    }

    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'team_id' => 'required',
                'edition_id' => 'required'
            ]
        );

        EventEditionTeam::create([
            'edition_id' => $request->get('edition_id'),
            'team_id' => $request->get('team_id')
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
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($eId, $tId)
    {
        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = EventEditionTeam::where('edition_id', $eId)
                ->where('team_id', $tId)
                ->firstOrFail();

            if($item){
                EventEditionTeam::where('edition_id', $eId)
                    ->where('team_id', $tId)
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
