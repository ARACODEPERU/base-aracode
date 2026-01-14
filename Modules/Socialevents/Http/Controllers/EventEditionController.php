<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;

class EventEditionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $P000010;

    protected $RPTABLE;

    public function __construct()
    {
        $this->RPTABLE = env('RECORDS_PAGE_TABLE') ?? 10;
        $this->P000010  = Parameter::where('parameter_code', 'P000010')->value('value_default');
    }

    public function index()
    {
        $editions = EventEdition::with('evento')->orderBy('start_date', 'DESC')->paginate(20);
        return Inertia::render('Socialevents::Editions/List',[
            'editions' => $editions
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $events = EvenEvent::get();
        $formats = getEnumValues('event_editions','competition_format');
        $tinyApiKey = $this->P000010;
        return Inertia::render('Socialevents::Editions/Create',[
            'eventos' => $events,
            'formats' => $formats,
            'tinyApiKey' => $tinyApiKey
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //dd($request->all());
        $this->validate(
            $request,
            [
                'event_id' => 'required',
                'year' => 'required',
                'name'   => 'required|max:255|string',
                'start_date' => 'required',
                'score_points_win'   => 'required',
                'score_points_draw'   => 'required',
                'score_points_loss'   => 'required',
                'inscription_fee'   => 'required',
                'min_players_per_team'   => 'required',
                'max_players_per_team'   => 'required',
                'yellow_price'   => 'required',
                'direct_red_price'   => 'required',
                'double_yellow_price'   => 'required',
            ]
        );

        $prize_details = $request->get('prize_details');
        $details = $request->get('details');

        $edition = EventEdition::create([
            'event_id' => $request->get('event_id'),
            'year' => $request->get('year'),
            'name' => $request->get('name'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'competition_format' => $request->get('competition_format'),
            'score_points_win' => $request->get('score_points_win'),
            'score_points_draw' => $request->get('score_points_draw'),
            'score_points_loss' => $request->get('score_points_loss'),
            'inscription_fee' => $request->get('inscription_fee'),
            'min_players_per_team' => $request->get('min_players_per_team'),
            'max_players_per_team' => $request->get('max_players_per_team'),
            'prize_details' =>htmlentities($prize_details, ENT_QUOTES, "UTF-8"),
            'details' =>htmlentities($details, ENT_QUOTES, "UTF-8"),
            'status' => $request->get('status') ? true : false,
            'yellow_price' => $request->get('yellow_price'),
            'direct_red_price' => $request->get('direct_red_price'),
            'double_yellow_price' => $request->get('double_yellow_price'),
        ]);

        $path = null;
        $destination = 'uploads/eventos/ediciones';
        $file = $request->file('file');

        if ($file) {
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $extension = $file->getClientOriginalExtension();
            $file_name = date('YmdHis') . '.' . $extension;
            $path = $request->file('file')->storeAs(
                $destination,
                $file_name,
                'public'
            );

            $edition->name_database_file = $original_name;
            $edition->path_database_file = $path;
            $edition->save();
        }

        return to_route('even_ediciones_listado');
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
        $events = EvenEvent::get();
        $formats = getEnumValues('event_editions','competition_format');
        $tinyApiKey = $this->P000010;
        $edicion = EventEdition::with('evento')->where('id', $id)->first();

        return Inertia::render('Socialevents::Editions/Edit',[
            'eventos' => $events,
            'formats' => $formats,
            'tinyApiKey' => $tinyApiKey,
            'edicion' => $edicion
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $id = $request->get('id');

        $this->validate(
            $request,
            [
                'event_id' => 'required',
                'year' => 'required',
                'name'   => 'required|max:255|string',
                'start_date' => 'required',
                'score_points_win'   => 'required',
                'score_points_draw'   => 'required',
                'score_points_loss'   => 'required',
                'inscription_fee'   => 'required',
                'min_players_per_team'   => 'required',
                'max_players_per_team'   => 'required',
                'yellow_price'   => 'required',
                'direct_red_price'   => 'required',
                'double_yellow_price'   => 'required',
            ]
        );

        $prize_details = $request->get('prize_details');
        $details = $request->get('details');

        $edition = EventEdition::find($id);
        $edition->update([
            'event_id' => $request->get('event_id'),
            'year' => $request->get('year'),
            'name' => $request->get('name'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'competition_format' => $request->get('competition_format'),
            'score_points_win' => $request->get('score_points_win'),
            'score_points_draw' => $request->get('score_points_draw'),
            'score_points_loss' => $request->get('score_points_loss'),
            'inscription_fee' => $request->get('inscription_fee'),
            'min_players_per_team' => $request->get('min_players_per_team'),
            'max_players_per_team' => $request->get('max_players_per_team'),
            'prize_details' =>htmlentities($prize_details, ENT_QUOTES, "UTF-8"),
            'details' =>htmlentities($details, ENT_QUOTES, "UTF-8"),
            'status' => $request->get('status') ? true : false,
            'yellow_price' => $request->get('yellow_price'),
            'direct_red_price' => $request->get('direct_red_price'),
            'double_yellow_price' => $request->get('double_yellow_price'),
        ]);

        $path = null;
        $destination = 'uploads/eventos/ediciones';
        $file = $request->file('file');

        if ($file) {
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $extension = $file->getClientOriginalExtension();
            $file_name = date('YmdHis') . '.' . $extension;
            $path = $request->file('file')->storeAs(
                $destination,
                $file_name,
                'public'
            );

            $edition->name_database_file = $original_name;
            $edition->path_database_file = $path;
            $edition->save();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = EventEdition::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

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
