<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Facades\Image;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Entities\EventEditionDiplomaTemplate;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Http\Requests\DiplomaLayoutRequest;
use Modules\Socialevents\Http\Requests\DiplomaTemplateRequest;
use Modules\Socialevents\Services\DiplomaPayloadService;
use Modules\Socialevents\Services\DiplomaTemplateService;
use Modules\Socialevents\Services\DiplomaTokenResolver;
use Modules\Socialevents\Services\EditionAwardService;
use Modules\Socialevents\Services\SportsOrganizationService;
use Modules\Socialevents\Support\DiplomaAwardCatalog;
use Modules\Socialevents\Support\DiplomaFonts;

/**
 * Panel de diplomas de una edición y editor Konva.
 *
 * Todo el módulo está acotado a la organización dueña de la edición mediante
 * SportsOrganizationService; ninguna consulta confía en el id recibido.
 */
class EventEditionDiplomaController extends Controller
{
    public function __construct(
        private SportsOrganizationService $sports,
        private EditionAwardService $awards,
        private DiplomaTemplateService $templates,
        private DiplomaPayloadService $payloads,
    ) {}

    public function index(int $editionId): Response
    {
        $user = request()->user();
        $this->sports->assertCanRead($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::with(['evento.organization'])->findOrFail($editionId);

        // Los modelos pertenecen al torneo: la edición los reutiliza, no los
        // duplica. Así un mismo diseño sirve para 2025 y 2026.
        $templates = $this->templates->listForEvent((int) $edition->event_id);

        $teams = EventEditionTeam::with('equipo')
            ->where('edition_id', $editionId)
            ->get()
            ->map(fn (EventEditionTeam $row) => [
                'id' => $row->team_id,
                'name' => $row->equipo?->name ?? 'Equipo',
                'logo_url' => $row->equipo?->logo_path
                    ? asset('storage/'.ltrim(str_replace('\\', '/', $row->equipo->logo_path), '/'))
                    : null,
            ])
            ->values()
            ->all();

        $players = EventEditionTeamPlayer::with(['person', 'team'])
            ->where('edition_id', $editionId)
            ->get()
            ->map(fn (EventEditionTeamPlayer $row) => [
                'person_id' => $row->person_id,
                'name' => $row->person?->full_name ?? 'Jugador',
                'team_id' => $row->team_id,
                'team_name' => $row->team?->name,
                'jersey_number' => $row->jersey_number,
                'position' => $row->position,
            ])
            ->sortBy('name')
            ->values()
            ->all();

        return Inertia::render('Socialevents::Editions/Diplomas/Index', [
            'sportsMode' => $this->sports->sportsMode(),
            'edition' => [
                'id' => $edition->id,
                'name' => $edition->name,
                'year' => $edition->year,
                'evento' => $edition->evento?->title,
                'landing_url' => $edition->landingUrl(),
                'landing_published' => (bool) $edition->landing_published,
            ],
            'awards' => $this->awards->listForEdition($editionId),
            'templates' => $templates->map(fn (EventEditionDiplomaTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'diploma_type' => $template->diploma_type,
                'type_label' => $template->typeLabel(),
                'year' => $template->year,
                'is_active' => (bool) $template->is_active,
                'width' => $template->canvas()['width'],
                'height' => $template->canvas()['height'],
                'background_url' => $this->templates->backgroundUrl($template),
                'elements_count' => count($template->layout['elements'] ?? []),
                'updated_at' => $template->updated_at?->format('d/m/Y H:i'),
            ])->values()->all(),
            // Selección explícita por premio: la vista filtra esta lista según
            // el tipo de destinatario de cada premiado.
            'availableTemplates' => $templates->map(fn (EventEditionDiplomaTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'diploma_type' => $template->diploma_type,
                'year' => $template->year,
                'is_active' => (bool) $template->is_active,
            ])->values()->all(),
            'awardCatalog' => DiplomaAwardCatalog::defaults(),
            'teams' => $teams,
            'players' => $players,
            'suggestions' => $this->awards->candidates($editionId),
            'eventId' => (int) $edition->event_id,
        ]);
    }

    public function store(DiplomaTemplateRequest $request, int $editionId): RedirectResponse
    {
        $user = $request->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::findOrFail($editionId);

        $award = $request->input('award_id')
            ? EventEditionAward::where('edition_id', $editionId)->findOrFail((int) $request->input('award_id'))
            : null;

        $diplomaType = $award
            ? $award->diplomaType()
            : (string) $request->input('diploma_type', EventEditionDiplomaTemplate::TYPE_PLAYER);

        $background = $request->hasFile('background')
            ? $request->file('background')
            : (string) $request->input('background_base64');

        $template = $this->templates->create(
            (int) $edition->event_id,
            $editionId,
            $diplomaType,
            (string) $request->input('name'),
            (int) ($request->input('year') ?: $edition->year),
            $background,
            (int) $user->id
        );

        return redirect()->route('even_ediciones_diplomas_editar', [
            'id' => $editionId,
            'templateId' => $template->id,
        ]);
    }

    public function editor(int $editionId, int $templateId): Response
    {
        $user = request()->user();
        $this->sports->assertCanRead($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::with(['evento.organization'])->findOrFail($editionId);
        $template = $this->findEventTemplate($edition, $templateId);

        $award = $this->awards->sampleOfType($editionId, $template->diploma_type);
        $samplePayload = $this->payloads->build($edition, $award, $template);

        return Inertia::render('Socialevents::Editions/Diplomas/Editor', [
            'sportsMode' => $this->sports->sportsMode(),
            'edition' => [
                'id' => $edition->id,
                'name' => $edition->name,
                'year' => $edition->year,
                'evento' => $edition->evento?->title,
            ],
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'award_key' => $template->award_key,
                'award_label' => $template->typeLabel(),
                'diploma_type' => $template->diploma_type,
                'year' => $template->year,
                'is_active' => (bool) $template->is_active,
                'width' => $template->canvas()['width'],
                'height' => $template->canvas()['height'],
                'background_url' => $this->templates->backgroundUrl($template),
                'layout' => $template->layout ?? ['version' => 1, 'elements' => []],
            ],
            'award' => [
                'id' => $award->id,
                'label' => $award->label,
                'recipient_type' => $award->recipient_type,
                'recipient_name' => $award->recipient_name,
                'team_name' => $award->team_name,
            ],
            'samplePayload' => $samplePayload,
            'endpoints' => [
                'update' => route('even_ediciones_diplomas_update', [$editionId, $template->id]),
                'background' => route('even_ediciones_diplomas_fondo', [$editionId, $template->id]),
                'asset' => route('even_ediciones_diplomas_asset', [$editionId, $template->id]),
                'back' => route('even_ediciones_diplomas', $editionId),
            ],
            'breadcrumbs' => [
                ['route' => route('even_ediciones_listado'), 'title' => 'Torneos'],
                ['route' => route('even_ediciones_diplomas', $editionId), 'title' => 'Diplomas'],
                ['title' => $template->typeLabel()],
            ],
            // Sólo las variables compatibles con el tipo del modelo.
            'tokens' => DiplomaTokenResolver::forType($template->diploma_type),
            'fonts' => array_map(
                fn (string $family) => [
                    'family' => $family,
                    'file' => DiplomaFonts::FILES[$family] ?? null,
                ],
                DiplomaFonts::FAMILIES
            ),
        ]);
    }

    public function update(DiplomaLayoutRequest $request, int $editionId, int $templateId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::findOrFail($editionId);
        $template = $this->findEventTemplate($edition, $templateId);

        $this->templates->saveLayout(
            $template,
            $request->validated()['layout'],
            $request->input('name')
        );

        return response()->json([
            'success' => true,
            'message' => 'Diseño guardado.',
            'updated_at' => $template->fresh()->updated_at?->format('d/m/Y H:i'),
        ]);
    }

    public function activate(int $editionId, int $templateId): JsonResponse
    {
        $user = request()->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::findOrFail($editionId);
        $template = $this->findEventTemplate($edition, $templateId);
        $this->templates->activate($template);

        return response()->json([
            'success' => true,
            'message' => 'El modelo quedó vigente para este torneo.',
        ]);
    }

    public function background(Request $request, int $editionId, int $templateId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $request->validate([
            'background' => ['required_without:background_base64', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'background_base64' => ['required_without:background', 'string'],
        ]);

        $edition = EventEdition::findOrFail($editionId);
        $template = $this->findEventTemplate($edition, $templateId);

        $result = $this->templates->replaceBackground(
            $template,
            $request->hasFile('background') ? $request->file('background') : (string) $request->input('background_base64')
        );

        return response()->json(array_merge(['success' => true], $result));
    }

    /**
     * Sube una imagen suelta del diseño (escudo, sello, firma).
     */
    public function asset(Request $request, int $editionId, int $templateId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
        ]);

        $edition = EventEdition::findOrFail($editionId);
        $this->findEventTemplate($edition, $templateId);

        $image = Image::make($request->file('image')->getRealPath());
        $image->resize(1200, 1200, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $path = 'socialevents/diplomas/event_'.$edition->event_id.'/assets/'
            .now()->format('YmdHis').'_'.mt_rand(1000, 9999).'.png';

        Storage::disk('public')->put($path, (string) $image->encode('png'));

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => asset('storage/'.$path),
            'width' => (int) $image->width(),
            'height' => (int) $image->height(),
        ]);
    }

    public function destroy(int $editionId, int $templateId): JsonResponse
    {
        $user = request()->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::findOrFail($editionId);
        $template = $this->findEventTemplate($edition, $templateId);
        $result = $this->templates->destroy($template);

        return response()->json([
            'success' => true,
            'deactivated' => $result === 'deactivated',
            'message' => $result === 'deactivated'
                ? 'El modelo ya tenía diplomas emitidos: se desactivó para no romper reimpresiones.'
                : 'Modelo eliminado.',
        ]);
    }

    /**
     * Asigna el modelo que se usará al emitir un premio concreto.
     */
    public function assignTemplate(Request $request, int $editionId, int $awardId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $data = $request->validate([
            'template_id' => ['nullable', 'integer'],
        ]);

        $award = EventEditionAward::where('edition_id', $editionId)->findOrFail($awardId);
        $template = $this->awards->assignTemplate($award, $data['template_id'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Modelo de diploma actualizado.',
            'template_id' => $template->template_id,
        ]);
    }

    /**
     * Busca el modelo dentro del torneo de la edición: impide que una edición
     * use (o edite) un modelo de otra organización.
     */
    private function findEventTemplate(EventEdition $edition, int $templateId): EventEditionDiplomaTemplate
    {
        return EventEditionDiplomaTemplate::where('event_id', $edition->event_id)
            ->findOrFail($templateId);
    }
}
