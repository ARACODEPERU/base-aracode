<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PettyCash;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleProduct;
use App\Models\Serie;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;

class EventEditionMatchSanctionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function collectionPenalties($id)
    {
        $edicion = EventEdition::find($id);
        $players = EventEditionTeamPlayer::with(['team', 'person', 'sanctions' => function($q) { $q->with('match')->where('is_paid', false); }])
        ->where('edition_id', $id)
        ->whereHas('sanctions', fn($q) => $q->where('is_paid', false))
        ->get()
        ->map(function($player) {
            $total = 0;
            // Agrupamos por partido para aplicar la regla de absorción
            $grouped = $player->sanctions->groupBy('match_id');

            foreach ($grouped as $matchSanctions) {
                $hasDoubleYellow = $matchSanctions->where('type', 'double_yellow')->first();

                if ($hasDoubleYellow) {
                    // Si hay doble amarilla, sumamos esa y cualquier roja directa,
                    // pero IGNORAMOS las amarillas simples de ese partido.
                    $total += $matchSanctions->whereIn('type', ['double_yellow', 'red'])->sum('amount_fine');
                } else {
                    // Si no hay doble amarilla, sumamos todo lo que haya
                    $total += $matchSanctions->sum('amount_fine');
                }
            }

            $player->total_debt = number_format($total, 2, '.', '');

            // Exponemos la fecha/ronda del partido en cada sancion sin pagar.
            $player->sanctions->transform(function ($sanction) {
                $match = $sanction->match;
                $sanction->match_round = $match?->round_number;
                $sanction->match_date = $match?->match_date?->format('d/m/Y');
                $sanction->match_label = $match
                    ? 'Fecha '.($match->round_number ?? '?').($match->match_date ? ' · '.$match->match_date->format('d/m/Y') : '')
                    : 'Sin partido asignado';
                return $sanction;
            });

            return $player;
        });

        // Partidos con resultado registrado de la edición (para el registro manual de tarjetas).
        $playedMatches = EventEditionMatch::with(['equipolocal:id,name', 'equipovisitante:id,name'])
            ->where('edition_id', $id)
            ->whereNotNull('score_h')
            ->whereNotNull('score_a')
            ->orderBy('match_date')
            ->get(['id', 'team_h_id', 'team_a_id', 'round_number', 'match_date', 'score_h', 'score_a'])
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'label' => sprintf(
                        'Fecha %s · %s %d-%d %s · %s',
                        $match->round_number ?? '?',
                        $match->equipolocal?->name ?? 'Local',
                        $match->score_h,
                        $match->score_a,
                        $match->equipovisitante?->name ?? 'Visitante',
                        $match->match_date?->format('d/m/y') ?? 's/f'
                    ),
                ];
            });

        // Todos los jugadores inscritos en la edición, agrupados por equipo (para el selector).
        $allPlayers = EventEditionTeamPlayer::with(['team:id,name', 'person:id,names'])
            ->where('edition_id', $id)
            ->get()
            ->groupBy('team_id')
            ->mapWithKeys(function ($teamPlayers) {
                $team = $teamPlayers->first()->team;
                $label = $team?->name ?? 'Equipo';

                $members = $teamPlayers
                    ->filter(fn ($tp) => $tp->person !== null)
                    ->map(function ($tp) {
                        return [
                            'player_id' => $tp->person_id,
                            'name' => $tp->person->full_name ?? $tp->person->names ?? 'Jugador sin nombre',
                        ];
                    })
                    ->values();

                return [$label => $members];
            });

        return Inertia::render('Socialevents::Editions/Sanctions',[
            'players' => $players,
            'edicion' => $edicion,
            'playedMatches' => $playedMatches,
            'allPlayers' => $allPlayers,
        ]);
    }

    /**
     * Genera el PDF del listado de sanciones pendientes de pago.
     */
    public function printSanctionsPdf($id)
    {
        $edicion = EventEdition::with('evento')->findOrFail($id);

        $players = EventEditionTeamPlayer::with(['team', 'person', 'sanctions' => function ($q) {
            $q->where('is_paid', false);
        }])
        ->where('edition_id', $id)
        ->whereHas('sanctions', fn ($q) => $q->where('is_paid', false))
        ->get();

        $rows = [];
        $total = 0;

        foreach ($players as $player) {
            $grouped = $player->sanctions->groupBy('match_id');

            foreach ($grouped as $matchSanctions) {
                $hasDoubleYellow = $matchSanctions->where('type', 'double_yellow')->isNotEmpty();

                // Aplica la misma regla de absorción del listado: si hay doble amarilla,
                // se ignoran las amarillas simples de ese partido.
                $effective = $hasDoubleYellow
                    ? $matchSanctions->whereIn('type', ['double_yellow', 'red'])
                    : $matchSanctions;

                foreach ($effective as $sanction) {
                    $amount = (float) $sanction->amount_fine;
                    $total += $amount;

                    $rows[] = [
                        'player_name' => $player->person?->full_name ?? 'Jugador',
                        'team_name'   => $player->team?->name ?? 'Equipo',
                        'card'        => $this->sanctionLabel($sanction->type),
                        'price'       => number_format($amount, 2, '.', ''),
                    ];
                }
            }
        }

        $pdf = Pdf::loadView('socialevents::sanctions.pdf.list', [
            'event_name' => $edicion->evento?->title ?? '',
            'edicion'    => $edicion,
            'rows'       => $rows,
            'total'      => number_format($total, 2, '.', ''),
        ]);

        return $pdf->stream('sanciones_edicion_' . $id . '_' . date('Ymd') . '.pdf');
    }

    private function sanctionLabel(?string $type): string
    {
        return match ($type) {
            'yellow'        => 'Amarilla',
            'double_yellow' => 'Doble Amarilla',
            'red'           => 'Roja Directa',
            default         => $type ?? '-',
        };
    }

    public function paySanctions(Request $request)
    {
        $playerId = $request->player_id; // ID de event_edition_team_players

        EventEditionMatchSanction::where('player_id', $playerId)
            ->where('is_paid', false)
            ->update([
                'is_paid' => true,
                'paid_at' => now(),
                // Aquí podrías guardar el ID de la nota de venta si ya la tienes
            ]);

        return back()->with('message', 'Sanciones regularizadas con éxito');
    }

    /**
     * Registra manualmente una tarjeta (amarilla/roja/doble amarilla) a un jugador
     * de un partido ya jugado, sin pasar por el acta del partido.
     */
    public function registerCard(Request $request, int $editionId): RedirectResponse
    {
        $validated = $request->validate([
            'match_id' => 'required|integer|exists:event_edition_matches,id',
            'player_id' => 'required|integer|exists:people,id',
            'type' => 'required|in:yellow,red,double_yellow',
            'minute' => 'nullable|string|max:5',
            'amount_fine' => 'nullable|numeric|min:0|max:9999.99',
        ], [
            'match_id.required' => 'Selecciona el partido.',
            'match_id.exists' => 'El partido seleccionado no existe.',
            'player_id.required' => 'Selecciona el jugador.',
            'player_id.exists' => 'El jugador seleccionado no existe.',
            'type.required' => 'Selecciona el tipo de tarjeta.',
            'amount_fine.max' => 'El monto no puede superar S/ 9999.99.',
        ]);

        // El partido debe pertenecer a la edición y estar jugado (con marcador).
        $match = EventEditionMatch::where('edition_id', $editionId)
            ->whereNotNull('score_h')
            ->whereNotNull('score_a')
            ->find($validated['match_id']);

        if (! $match) {
            return back()->withErrors(['match_id' => 'El partido seleccionado no pertenece a esta edición o aún no tiene resultado registrado.']);
        }

        // El jugador debe estar inscrito en la edición.
        $exists = EventEditionTeamPlayer::where('edition_id', $editionId)
            ->where('person_id', $validated['player_id'])
            ->exists();

        if (! $exists) {
            return back()->withErrors(['player_id' => 'El jugador seleccionado no está inscrito en esta edición.']);
        }

        $amount = isset($validated['amount_fine']) && $validated['amount_fine'] !== null
            ? round((float) $validated['amount_fine'], 2)
            : (float) match ($validated['type']) {
                'yellow' => $match->edicion->yellow_price ?? 0,
                'red' => $match->edicion->direct_red_price ?? 0,
                'double_yellow' => $match->edicion->double_yellow_price ?? 0,
            };

        DB::transaction(function () use ($validated, $amount) {
            if ($validated['type'] === 'double_yellow') {
                // Mismo comportamiento que el acta: la doble amarilla registra la
                // expulsión + las 2 amarillas individuales (para el historial).
                EventEditionMatchSanction::create([
                    'match_id' => $validated['match_id'],
                    'player_id' => $validated['player_id'],
                    'type' => 'double_yellow',
                    'minute' => $validated['minute'] ?? null,
                    'amount_fine' => $amount,
                ]);

                for ($i = 1; $i <= 2; $i++) {
                    EventEditionMatchSanction::create([
                        'match_id' => $validated['match_id'],
                        'player_id' => $validated['player_id'],
                        'type' => 'yellow',
                        'minute' => $validated['minute'] ?? null,
                        'amount_fine' => 0,
                    ]);
                }
            } else {
                EventEditionMatchSanction::create([
                    'match_id' => $validated['match_id'],
                    'player_id' => $validated['player_id'],
                    'type' => $validated['type'],
                    'minute' => $validated['minute'] ?? null,
                    'amount_fine' => $amount,
                ]);
            }
        });

        return back()->with('success', 'Tarjeta registrada correctamente.');
    }

    public function paymentStore(Request $request)
    {

        //dd($request->all());
        try {
            $res = DB::transaction(function () use ($request) {


                $local_id = Auth::user()->local_id;
                $serie = Serie::where('document_type_id', '5')
                    ->where('local_id', $local_id)
                    ->first();

                $petty_cash = PettyCash::firstOrCreate([
                    'user_id' => Auth::id(),
                    'state' => 1,
                    'local_sale_id' => $local_id
                ], [
                    'date_opening' => Carbon::now()->format('Y-m-d'),
                    'time_opening' => date('H:i:s'),
                    'income' => 0
                ]);

                $serie_id = $serie->id;

                $sale = Sale::create([
                    'sale_date' => $request->get('sale_date'),
                    'user_id' => Auth::id(),
                    'client_id' => $request->get('player_id'),
                    'local_id' => $local_id,
                    'total' => $request->get('total'),
                    'advancement' => $request->get('total'),
                    'total_discount' => 0,
                    'payments' => json_encode($request->get('payments')),
                    'petty_cash_id' => $petty_cash->id,
                    'physical' => 1,

                ]);

                SaleDocument::create([
                    'sale_id'   => $sale->id,
                    'serie_id'  => $serie_id,
                    'number'    => str_pad($serie->number, 9, '0', STR_PAD_LEFT),
                    'overall_total'     => $request->get('total'),
                    'user_id'  => Auth::id(),
                    'invoice_type_doc' => '80',
                    'invoice_serie' => $serie->description,
                    'invoice_correlative' => $serie->number,
                    'invoice_razon_social' => $request->get('responsable')
                ]);

                $serie->increment('number', 1);

                $products = $request->get('items');

                // IDs de las sanciones que el delegado dejó en el modal para pagar.
                $sanctionIds = collect($products)->pluck('id')->filter()->unique()->values()->all();

                foreach ($products as $produc) {
                    SaleProduct::create([
                        'sale_id' => $sale->id,
                        //'product_id' => $produc['id'],
                        'product' => json_encode($produc),
                        'saleProduct' => json_encode($produc),
                        'price' => $produc['amount_fine'],
                        'discount' => 0,
                        'quantity' => 1,
                        'total' => $produc['amount_fine'],
                        'entity_name_product' => EventEditionMatchSanction::class,
                        'advancement' => $produc['amount_fine']
                    ]);
                }

                // Marcamos como pagadas SOLO las sanciones incluidas en la venta
                // (se paga de una en una / por partido, no todas las pendientes del jugador).
                if ($sanctionIds) {
                    EventEditionMatchSanction::whereIn('id', $sanctionIds)
                        ->where('player_id', $request->get('player_id'))
                        ->update([
                            'is_paid' => true,
                            'paid_at' => now(),
                            'note_sale_id' => $sale->id,
                        ]);
                }

                return $sale;
            });

            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['message' => $e]);
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
