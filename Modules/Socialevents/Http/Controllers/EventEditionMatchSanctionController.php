<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PettyCash;
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
        $players = EventEditionTeamPlayer::with(['team', 'person', 'sanctions' => function($q) { $q->where('is_paid', false); }])
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
            return $player;
        });

        return Inertia::render('Socialevents::Editions/Sanctions',[
            'players' => $players,
            'edicion' => $edicion
        ]);
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

                $match_id = null;

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

                    $match_id = EventEditionMatchSanction::where('id', $produc['id'])->value('match_id');

                }

                EventEditionMatchSanction::where('player_id', $request->get('player_id'))
                        ->where('match_id', $match_id)
                        ->update([
                            'is_paid' => true,
                            'paid_at' => now(),
                            'note_sale_id' => $sale->id,
                        ]);

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
