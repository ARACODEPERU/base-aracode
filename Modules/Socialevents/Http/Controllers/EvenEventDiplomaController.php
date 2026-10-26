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
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Entities\EventEditionDiploma;
use Modules\Socialevents\Entities\EventEditionDiplomaTemplate;
use Modules\Socialevents\Http\Requests\DiplomaLayoutRequest;
use Modules\Socialevents\Http\Requests\DiplomaTemplateRequest;
use Modules\Socialevents\Services\DiplomaPayloadService;
use Modules\Socialevents\Services\DiplomaTemplateService;
use Modules\Socialevents\Services\DiplomaTokenResolver;
use Modules\Socialevents\Services\EditionAwardService;
use Modules\Socialevents\Services\SportsOrganizationService;
use Modules\Socialevents\Support\DiplomaFonts;

/**
 * Biblioteca de modelos de diploma de un torneo.
 *
 * Los modelos pertenecen al torneo y se reutilizan en todas sus ediciones. Esta
 * pantalla es la que se abre desde el listado de torneos; la pantalla de una
 * edición sólo elige cuál usar para cada premiado.
 */
class EvenEventDiplomaController extends Controller
{
    public function __construct(
        private SportsOrganizationService $sports,
        private DiplomaTemplateService $templates,
        private DiplomaPayloadService $payloads,
        private EditionAwardService $awards,
    ) {}

    public function index(int $eventId): Response
    {
        $user = request()->user();
        $this->sports->assertCanRead($user);
        $this->sports->assertOwnedEvent($user, $eventId);

        $event = EvenEvent::findOrFail($eventId);

        $editions = EventEdition::where('event_id', $eventId)
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get(['id', 'name', 'year']);

        $templates = $this->templates->listForEvent($eventId);

        $diplomaCounts = EventEditionDiploma::whereIn('template_id', $templates->pluck('id'))
            ->selectRaw('template_id, count(*) as total')
            ->groupBy('template_id')
            ->pluck('total', 'template_id');

        $awardCounts = EventEditionAward::whereIn('template_id', $templates->pluck('id'))
            ->selectRaw('template_id, count(*) as total')
            ->groupBy('template_id')
            ->pluck('total', 'template_id');

        return Inertia::render('Socialevents::Diplomas/Index', [
            'sportsMode' => $this->sports->sportsMode(),
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
            ],
            'editions' => $editions->map(fn (EventEdition $edition) => [
                'id' => $edition->id,
                'name' => $edition->name,
                'year' => $edition->year,
            ])->values()->all(),
            'templates' => $templates->map(function (EventEditionDiplomaTemplate $template) use ($diplomaCounts, $awardCounts) {
                $diplomas = (int) ($diplomaCounts[$template->id] ?? 0);
                $awards = (int) ($awardCounts[$template->id] ?? 0);

                return [
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
                    'diplomas_count' => $diplomas,
                    'awards_count' => $awards,
                    'in_use' => $diplomas > 0 || $awards > 0,
                ];
            })->values()->all(),
            'types' => [
                ['value' => EventEditionDiplomaTemplate::TYPE_TEAM, 'label' => 'Por equipo'],
                ['value' => EventEditionDiplomaTemplate::TYPE_PLAYER, 'label' => 'Por jugador'],
            ],
            'defaultEditionId' => $editions->first()?->id,
        ]);
    }

    public function store(DiplomaTemplateRequest $request, int $eventId): RedirectResponse
    {
        $user = $request->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEvent($user, $eventId);

        $edition = EventEdition::where('event_id', $eventId)
            ->findOrFail((int) $request->input('edition_id') ?: EventEdition::where('event_id', $eventId)->value('id'));

        $background = $request->hasFile('background')
            ? $request->file('background')
            : (string) $request->input('background_base64');

        $template = $this->templates->create(
            $eventId,
            (int) $edition->id,
            (string) $request->input('diploma_type'),
            (string) $request->input('name'),
            (int) ($request->input('year') ?: $edition->year),
            $background,
            (int) $user->id
        );

        return redirect()->route('even_eventos_diplomas_editar', [
            'id' => $eventId,
            'templateId' => $template->id,
        ]);
    }

    public function editor(int $eventId, int $templateId): Response
    {
        $user = request()->user();
        $this->sports->assertCanRead($user);
        $this->sports->assertOwnedEvent($user, $eventId);

        $event = EvenEvent::findOrFail($eventId);
        $template = $this->findTemplate($eventId, $templateId);

        $edition = EventEdition::with(['evento'])->findOrFail($template->edition_id);
        $award = $this->awards->sampleOfType((int) $edition->id, $template->diploma_type);

        return Inertia::render('Socialevents::Editions/Diplomas/Editor', [
            'sportsMode' => $this->sports->sportsMode(),
            'edition' => [
                'id' => $edition->id,
                'name' => $edition->name,
                'year' => $edition->year,
                'evento' => $event->title,
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
            'samplePayload' => $this->payloads->build($edition, $award, $template),
            'tokens' => DiplomaTokenResolver::forType($template->diploma_type),
            'fonts' => array_map(
                fn (string $family) => [
                    'family' => $family,
                    'file' => DiplomaFonts::FILES[$family] ?? null,
                ],
                DiplomaFonts::FAMILIES
            ),
            'endpoints' => [
                'update' => route('even_eventos_diplomas_update', [$eventId, $template->id]),
                'background' => route('even_eventos_diplomas_fondo', [$eventId, $template->id]),
                'asset' => route('even_eventos_diplomas_asset', [$eventId, $template->id]),
                'back' => route('even_eventos_diplomas', $eventId),
            ],
            'breadcrumbs' => [
                ['route' => route('even_ediciones_listado'), 'title' => 'Torneos'],
                ['route' => route('even_eventos_diplomas', $eventId), 'title' => 'Diplomas'],
                ['title' => $template->typeLabel()],
            ],
        ]);
    }

    public function update(DiplomaLayoutRequest $request, int $eventId, int $templateId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEvent($user, $eventId);

        $template = $this->findTemplate($eventId, $templateId);

        $this->templates->saveLayout(
            $template,
            $request->validated()['layout'],
            $request->input('name')
        );

        return response()->json([
            'success' => true,
            'message' => 'Modelo guardado.',
            'updated_at' => $template->fresh()->updated_at?->format('d/m/Y H:i'),
        ]);
    }

    public function activate(Request $request, int $eventId, int $templateId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEvent($user, $eventId);

        $template = $this->findTemplate($eventId, $templateId);
        $active = $request->boolean('active', true);

        $this->templates->setActive($template, $active);

        return response()->json([
            'success' => true,
            'message' => $active ? 'Modelo vigente.' : 'Modelo desactivado.',
            'is_active' => $active,
        ]);
    }

    public function background(Request $request, int $eventId, int $templateId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEvent($user, $eventId);

        $request->validate([
            'background' => ['required_without:background_base64', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'background_base64' => ['required_without:background', 'string'],
        ]);

        $template = $this->findTemplate($eventId, $templateId);

        $result = $this->templates->replaceBackground(
            $template,
            $request->hasFile('background') ? $request->file('background') : (string) $request->input('background_base64')
        );

        return response()->json(array_merge(['success' => true], $result));
    }

    /**
     * Sube una imagen suelta del modelo (escudo, sello, firma).
     */
    public function asset(Request $request, int $eventId, int $templateId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEvent($user, $eventId);

        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
        ]);

        $this->findTemplate($eventId, $templateId);

        $image = Image::make($request->file('image')->getRealPath());
        $image->resize(1200, 1200, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $path = 'socialevents/diplomas/event_'.$eventId.'/assets/'
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

    public function destroy(int $eventId, int $templateId): JsonResponse
    {
        $user = request()->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEvent($user, $eventId);

        $template = $this->findTemplate($eventId, $templateId);
        $result = $this->templates->destroy($template);

        return response()->json([
            'success' => true,
            'deactivated' => $result === 'deactivated',
            'message' => $result === 'deactivated'
                ? 'El modelo ya tenía diplomas emitidos: se desactivó para conservar las reimpresiones.'
                : 'Modelo eliminado.',
        ]);
    }

    private function findTemplate(int $eventId, int $templateId): EventEditionDiplomaTemplate
    {
        return EventEditionDiplomaTemplate::where('event_id', $eventId)->findOrFail($templateId);
    }
}
