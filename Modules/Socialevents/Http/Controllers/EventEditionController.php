<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Services\PositionTableService;

class EventEditionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $P000010;

    protected $RPTABLE;

    protected $positionService;

    public function __construct()
    {
        $this->RPTABLE = env('RECORDS_PAGE_TABLE') ?? 10;
        $this->P000010  = Parameter::where('parameter_code', 'P000010')->value('value_default');
        $this->positionService = new PositionTableService();
    }

    public function index()
    {
        $editions = EventEdition::with([
            'evento',
            'equipos'
        ])
            ->orderBy('start_date', 'DESC')
            ->paginate(20);

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
        return Inertia::render('Socialevents::Editions/Create',[
            'eventos' => $events,
            'formats' => $formats,
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
                ...$this->publicationValidationRules(),
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
            'prize_details' => $prize_details,
            'details' => $details,
            'status' => 'pending',
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
        $edicion = EventEdition::with('evento')->where('id', $id)->first();

        return Inertia::render('Socialevents::Editions/Edit',[
            'eventos' => $events,
            'formats' => $formats,
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
                ...$this->publicationValidationRules($id),
            ]
        );

        $prize_details = $request->get('prize_details');
        $details = $request->get('details');

        $edition = EventEdition::findOrFail($id);
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
            'prize_details' => $prize_details,
            'details' => $details,
            'status' => $request->get('status') ?? 'pending',
            'yellow_price' => $request->get('yellow_price'),
            'direct_red_price' => $request->get('direct_red_price'),
            'double_yellow_price' => $request->get('double_yellow_price'),
            ...$this->publicationAttributesFromRequest($request),
        ]);

        $this->storeEditionFiles($request, $edition);

        return to_route('even_ediciones_listado');
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

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:finished',
        ]);

        $edition = EventEdition::findOrFail($id);

        if ($edition->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'La edición no está en progreso.'
            ], 400);
        }

        // Get standings to find the champion
        $standings = $this->positionService->getStandings($id);
        $champion = collect($standings)->firstWhere('position', 1);

        if ($champion) {
            EventEditionTeam::where('edition_id', $id)
                ->where('team_id', $champion['team_id'])
                ->update(['is_champion' => true]);
        }

        $edition->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.'
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function publicationValidationRules(?int $editionId = null): array
    {
        return [
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_whatsapp' => 'nullable|string|max:50',
            'landing_published' => 'nullable|boolean',
            'mobile_enabled' => 'nullable|boolean',
            'public_slug' => [
                'nullable',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('event_editions', 'public_slug')->ignore($editionId),
            ],
            'branding_accent_color' => 'nullable|string|max:20',
            'landing_hero_file' => 'nullable|image|max:5120',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publicationAttributesFromRequest(Request $request): array
    {
        $slug = $request->input('public_slug');
        if (is_string($slug) && $slug !== '') {
            $slug = Str::slug($slug);
        } else {
            $slug = null;
        }

        $accent = $request->input('branding_accent_color');
        $branding = null;
        if (is_string($accent) && $accent !== '') {
            $branding = ['accent_color' => $accent];
        }

        return [
            'contact_name' => $request->input('contact_name'),
            'contact_phone' => $request->input('contact_phone'),
            'contact_whatsapp' => $request->input('contact_whatsapp'),
            'landing_published' => $request->boolean('landing_published'),
            'mobile_enabled' => $request->boolean('mobile_enabled', true),
            'public_slug' => $slug,
            'branding' => $branding,
        ];
    }

    private function storeEditionFiles(Request $request, EventEdition $edition): void
    {
        $destination = 'uploads/eventos/ediciones';
        $file = $request->file('file');

        if ($file) {
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $extension = $file->getClientOriginalExtension();
            $file_name = date('YmdHis') . '_bases.' . $extension;
            $path = $file->storeAs($destination, $file_name, 'public');

            $edition->name_database_file = $original_name;
            $edition->path_database_file = $path;
            $edition->save();
        }

        $heroFile = $request->file('landing_hero_file');
        if ($heroFile) {
            $extension = $heroFile->getClientOriginalExtension();
            $file_name = date('YmdHis') . '_hero.' . $extension;
            $path = $heroFile->storeAs($destination . '/landing', $file_name, 'public');
            $edition->landing_hero_image = $path;
            $edition->save();
        }
    }
}
