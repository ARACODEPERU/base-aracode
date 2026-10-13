<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionPointAdjustment;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamBonusPoint;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Services\PositionTableService;
use Modules\Socialevents\Services\SportsOrganizationService;

class EventEditionTeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead(request()->user());
        $sports->assertOwnedEdition(request()->user(), (int) $id);
        $teams = EventTeam::query()
            ->when($sports->sportsMode() && ! $sports->isSuperAdmin(request()->user()), fn ($query) => $query->where('organization_id', $sports->organizationId(request()->user())))
            ->get();

        $currentEquipment = EventEditionTeam::with('equipo')
            ->where('edition_id', $id)
            ->orderByRaw('CASE WHEN matches_played = 0 THEN 1 ELSE 0 END') // Equipos sin partidos al final
            ->orderByRaw('(points + bonus_points) DESC') // 1° Más puntos totales (incluye extras) arriba
            ->orderBy('goal_difference', 'desc')  // 2° Mejor diferencia de goles
            ->orderBy('goals_for', 'desc')        // 3° Más goles marcados
            ->orderBy('matches_won', 'desc')      // 4° Más partidos ganados
            ->get();

        // Historico de puntos extra de la edicion.
        $bonusHistory = EventEditionTeamBonusPoint::with('team')
            ->where('edition_id', $id)
            ->orderByDesc('id')
            ->get();

        // Ajustes administrativos de puntos (sanciones) para los badges.
        $sanctionAdjustments = EventEditionPointAdjustment::with('team:id,name')
            ->where('edition_id', $id)
            ->orderByDesc('id')
            ->get();

        $edicion = EventEdition::findOrFail($id);

        return Inertia::render('Socialevents::Editions/Teams', [
            'teams' => $teams,
            'currentEquipment' => $currentEquipment,
            'edicion' => $edicion,
            'bonusHistory' => $bonusHistory,
            'sanctionAdjustments' => $sanctionAdjustments,
        ]);
    }

    public function store(Request $request)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $this->validate(
            $request,
            [
                'team_id' => 'required',
                'edition_id' => 'required'
            ]
        );

        $sports->assertOwnedEdition(request()->user(), (int) $request->input('edition_id'));
        $sports->assertOwnedTeam(request()->user(), (int) $request->input('team_id'));
        abort_unless(EventEdition::findOrFail((int) $request->input('edition_id'))->equipos()->where('team_id', (int) $request->input('team_id'))->doesntExist(), 409);
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
        app(SportsOrganizationService::class)->assertOwnedEdition(request()->user(), (int) $id);
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
        app(SportsOrganizationService::class)->assertCanWrite($request->user());
        app(SportsOrganizationService::class)->assertOwnedEdition($request->user(), (int) $id);
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($eId, $tId)
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

    public function printTeamRoster($editionId, $teamId)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead(request()->user());
        $sports->assertOwnedEditionTeam(request()->user(), (int) $editionId, (int) $teamId);
        $edition = EventEdition::with('evento')->findOrFail($editionId);
        $editionTeam = EventEditionTeam::with('equipo.manager')->where('edition_id', $editionId)->where('team_id', $teamId)->firstOrFail();
        $team = $editionTeam->equipo;

        $data = [
            'event_name'   => $edition->evento->title,
            'edition_name' => $edition->name,
            'team_name'    => $team->name,
            'manager_name' => $team->manager?->formatted_name ?? 'Sin asignar',
        ];

        $pdf = Pdf::loadView('socialevents::teams.pdf.team_roster', [
            'data' => $data
        ]);

        Storage::disk('public')->deleteDirectory('temp_pdfs');
        Storage::disk('public')->makeDirectory('temp_pdfs');

        $fileName = 'ficha_inscripcion_' . $team->id . '_' . date('Ymd') . '.pdf';
        $filePath = 'temp_pdfs/' . $fileName;

        Storage::disk('public')->put($filePath, $pdf->output());

        return response()->json([
            'url' => asset('storage/' . $filePath)
        ]);
    }

    /**
     * Historial de puntos extra otorgados en la edicion.
     */
    public function bonusPointsIndex($id)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead(request()->user());
        $sports->assertOwnedEdition(request()->user(), (int) $id);
        $bonus = EventEditionTeamBonusPoint::with('team')
            ->where('edition_id', $id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'bonus' => $bonus->map(fn ($b) => [
                'id' => $b->id,
                'team_id' => $b->team_id,
                'team_name' => $b->team?->name ?? 'Equipo',
                'points' => $b->points,
                'reason' => $b->reason,
                'created_at' => $b->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Otorga puntos extra a uno o varios equipos de la edicion.
     */
    public function bonusPointsStore(Request $request, $editionId)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $sports->assertOwnedEdition($request->user(), (int) $editionId);
        $validated = $request->validate([
            'teams' => ['required', 'array', 'min:1'],
            'teams.*' => ['required', 'integer'],
            'points' => ['required', 'integer', 'min:1', 'max:999'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            DB::beginTransaction();

            $teamIds = array_values(array_unique(array_map('intval', $validated['teams'])));
            foreach ($teamIds as $teamId) {
                $sports->assertOwnedEditionTeam($request->user(), (int) $editionId, $teamId);
            }

            // Validar que los equipos pertenezcan a la edicion.
            $inscritos = EventEditionTeam::where('edition_id', $editionId)
                ->whereIn('team_id', $teamIds)
                ->pluck('team_id')
                ->all();

            if (count($inscritos) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ninguno de los equipos seleccionados pertenece a esta edicion.',
                ], 422);
            }

            foreach ($inscritos as $teamId) {
                EventEditionTeamBonusPoint::create([
                    'edition_id' => $editionId,
                    'team_id' => $teamId,
                    'points' => $validated['points'],
                    'reason' => $validated['reason'],
                    'created_by' => Auth::id(),
                ]);
            }

            // Recalcular bonus acumulado de los equipos afectados.
            foreach ($inscritos as $teamId) {
                $editionTeam = EventEditionTeam::where('edition_id', $editionId)
                    ->where('team_id', $teamId)
                    ->first();
                $editionTeam?->recalculateBonus();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Puntos extra otorgados a '.count($inscritos).' equipo(s).',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Elimina un registro de punto extra y recalcula el bonus del equipo.
     */
    public function bonusPointsDestroy($editionId, $bonusId)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite(request()->user());
        $sports->assertOwnedEdition(request()->user(), (int) $editionId);
        $message = null;
        $success = false;

        try {
            DB::beginTransaction();

            $bonus = EventEditionTeamBonusPoint::where('id', $bonusId)
                ->where('edition_id', $editionId)
                ->firstOrFail();

            $teamId = $bonus->team_id;
            $bonus->delete();

            $editionTeam = EventEditionTeam::where('edition_id', $editionId)
                ->where('team_id', $teamId)
                ->first();
            $editionTeam?->recalculateBonus();

            DB::commit();

            $message = 'Punto extra eliminado correctamente';
            $success = true;
        } catch (\Exception $e) {
            DB::rollBack();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * Recalcula toda la tabla de posiciones de la edicion (puntos, bonus y rank).
     * Util para cuando la acta ya fue cerrada y no se recalculo automaticamente.
     */
    public function recalculateTable($editionId)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite(request()->user());
        $sports->assertOwnedEdition(request()->user(), (int) $editionId);
        try {
            $edition = EventEdition::findOrFail($editionId);
            app(PositionTableService::class)->updateTablePositions($editionId);

            return response()->json([
                'success' => true,
                'message' => 'Tabla de posiciones recalculada correctamente.',
                'edition' => $edition->name,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo recalcular la tabla: '.$e->getMessage(),
            ], 422);
        }
    }
}
