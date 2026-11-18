<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\CMS\Entities\CmsLanding;
use Modules\CMS\Entities\CmsSubscriber;
use Modules\CMS\Http\Requests\CmsLandingRequest;

class OneFreeCourseController extends Controller
{
    protected $P000010; ///token Tiny

    public function __construct()
    {
        $this->P000010  = Parameter::where('parameter_code', 'P000010')->value('value_default');
    }

    public function index($id)
    {
        $courses = AcaCourse::where('type_description','<>','Programas de Especialización')->get();

        $landingPage = CmsLanding::where('menu_id', $id)->first();

        return Inertia::render('CMS::Landings/CourseFree',[
            'courses' => $courses,
            'tinykey' => $this->P000010,
            'landingPage' => $landingPage,
            // Atribucion de los leads del formulario para los enlaces UTM del landing.
            'utmStats' => $this->getUtmStatsData($landingPage)
        ]);
    }

    public function store(CmsLandingRequest $request): RedirectResponse
    {
        $menu = $request->get('menu_id');
        // $menu = '01';
        try {
            // Verificamos si viene una imagen
            $mainImagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $filename = date('YmdHis') . '.' . $extension;

                // lo guarda en storage/app/public/uploads/cmsLanding con el nombre personalizado
                $mainImagePath = $file->storeAs('uploads/cmsLanding', $filename, 'public');
            }
            //dd($request->get('landing_id'));
            if (!$request->filled('landing_id')) {

                // === CREAR NUEVO ===
                CmsLanding::create([
                    'title'             => $request->get('title'),
                    'slug'              => $request->get('slug'),
                    'free_courses_count' => $this->resolveFreeCoursesCount($request),
                    'description_short' => $request->get('description_short'),
                    'description_long'  => $request->get('description_long'),
                    'main_image'        => $mainImagePath,
                    'data_related'      => $request->get('data_related') ?? null,
                    'data_related2'     => $request->get('data_related2') ?? null,
                    'additional_field1' => $request->get('additional_field1'),
                    'additional_field2' => $request->get('additional_field2'),
                    'additional_field3' => $request->get('additional_field3'),
                    'date_start'        => $request->get('date_start') ?? null,
                    'date_end'          => $request->get('date_end') ?? null,
                    'status'            => $request->get('status', 1),
                    'menu_id'           => $menu
                ]);

                return to_route('cms_landing_course_free', $menu)
                    ->with('success', 'Landing creada correctamente.');
            } else {
                // === ACTUALIZAR ===
                $landing = CmsLanding::findOrFail($request->get('landing_id'));

                if ($mainImagePath && $landing->main_image) {
                    Storage::disk('public')->delete($landing->main_image);
                }

                $landing->update([
                    'title'             => $request->get('title'),
                    'slug'              => $request->get('slug'),
                    'free_courses_count' => $this->resolveFreeCoursesCount($request),
                    'description_short' => $request->get('description_short'),
                    'description_long'  => $request->get('description_long'),
                    'main_image'        => $mainImagePath ?? $landing->main_image,
                    'data_related'      => $request->get('data_related') ?? null,
                    'data_related2'     => $request->get('data_related2') ?? null,
                    'additional_field1' => $request->get('additional_field1'),
                    'additional_field2' => $request->get('additional_field2'),
                    'additional_field3' => $request->get('additional_field3'),
                    'date_start'        => $request->get('date_start') ?? null,
                    'date_end'          => $request->get('date_end') ?? null,
                    'status'            => $request->get('status', 1),
                ]);

                return to_route('cms_landing_course_free', $menu)
                    ->with('success', 'Landing actualizada correctamente.');
            }

        } catch (\Exception $e) {
            return to_route('cms_landing_course_free', $menu)
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Cantidad de cursos gratis que muestra el landing: 1 como minimo,
     * 3 como maximo, sin importar lo que llegue del formulario.
     */
    private function resolveFreeCoursesCount(Request $request): int
    {
        $count = (int) $request->get('free_courses_count', 1);

        return max(1, min(3, $count));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('cms::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('cms::edit');
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

    /**
     * Guarda la configuracion de los enlaces UTM del landing del curso gratuito.
     */
    public function updateUtmConfig(Request $request, $id): JsonResponse
    {
        $this->validate(
            $request,
            [
                'campaign' => 'nullable|string|max:255',
                'base_url' => 'nullable|string|max:500',
                'channels' => 'nullable|array',
                'channels.*.id' => 'required|string|max:100',
                'channels.*.term' => 'nullable|string|max:500',
                'channels.*.content' => 'nullable|string|max:500',
                'channels.*.active' => 'nullable|boolean',
            ]
        );

        $landing = CmsLanding::where('menu_id', $id)->firstOrFail();

        $campaign = trim((string) $request->get('campaign'));

        $landing->update([
            'utm_config' => [
                'campaign' => $campaign !== '' ? $campaign : $this->defaultUtmCampaign($landing),
                'base_url' => rtrim(trim((string) $request->get('base_url')), '/'),
                'channels' => $request->channels ?? [],
            ],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Estadisticas de los leads del formulario segun el UTM con el que llegaron.
     */
    public function getUtmStats(Request $request, $id): JsonResponse
    {
        $landing = CmsLanding::where('menu_id', $id)->first();

        [$start, $end] = $this->parseDateRange($request->get('dates'));

        return response()->json($this->getUtmStatsData($landing, $start, $end));
    }

    /**
     * Campana por defecto del landing: se usa cuando el usuario no define una.
     */
    private function defaultUtmCampaign(?CmsLanding $landing): string
    {
        $slug = trim((string) ($landing->slug ?? ''));

        return 'landing_' . str_replace('-', '_', $slug !== '' ? $slug : 'curso_gratis');
    }

    /**
     * Convierte el rango de fechas del calendario ("2026-01-01 to 2026-01-31")
     * en un par [inicio, fin].
     */
    private function parseDateRange($dates): array
    {
        if (! $dates) {
            return [null, null];
        }

        foreach ([' to ', ' a '] as $separator) {
            if (str_contains($dates, $separator)) {
                [$start, $end] = explode($separator, $dates, 2);

                return [trim($start), trim($end)];
            }
        }

        return [$dates, $dates];
    }

    /**
     * Leads (suscriptores) del landing agrupados por origen (traffic_source),
     * con el desglose social por canal y de "Otra pagina" por dominio.
     */
    private function getUtmStatsData(?CmsLanding $landing, $start = null, $end = null): array
    {
        $config = $landing ? ($landing->utm_config ?? []) : [];

        $campaign = ! empty($config['campaign'])
            ? $config['campaign']
            : $this->defaultUtmCampaign($landing);

        $slug = trim((string) ($landing->slug ?? ''));

        $scope = function ($q) use ($campaign, $slug, $start, $end) {
            $q->where(function ($w) use ($campaign, $slug) {
                $w->where('utm_campaign', $campaign);

                // Tambien cuentan los envios hechos desde este landing sin UTM:
                // el formulario guarda la URL de origen en landing_url.
                if ($slug !== '') {
                    $w->orWhere('landing_url', 'like', '%' . $slug . '%');
                }
            });

            if ($start && $end) {
                $q->whereDate('created_at', '>=', $start)
                    ->whereDate('created_at', '<=', $end);
            }
        };

        $subscribers = CmsSubscriber::where($scope)
            ->select('traffic_source', DB::raw('COUNT(*) as total'))
            ->groupBy('traffic_source')
            ->orderByDesc('total')
            ->get();

        $socialSubscribers = CmsSubscriber::where($scope)
            ->where('traffic_source', 'social')
            ->select('utm_source', 'utm_medium', DB::raw('COUNT(*) as total'))
            ->groupBy('utm_source', 'utm_medium')
            ->orderByDesc('total')
            ->get();

        $referrerSubscribers = $this->groupByRefererHost(
            CmsSubscriber::where($scope)
                ->where('traffic_source', 'referrer')
                ->whereNotNull('referer')
                ->where('referer', '!=', '')
                ->select('referer')
                ->get()
        );

        return [
            'campaign' => $campaign,
            'subscribers' => $subscribers,
            'social_detail' => $socialSubscribers,
            'referrer_detail' => $referrerSubscribers,
        ];
    }

    /**
     * Agrupa filas de referer (URL completa) por dominio de origen, ordenado de mayor a menor.
     */
    private function groupByRefererHost($rows)
    {
        $hosts = [];

        foreach ($rows as $row) {
            $host = $row->referer ? parse_url($row->referer, PHP_URL_HOST) : null;
            $host = $host ?: (str_contains($row->referer ?? '', '.') ? $row->referer : 'Desconocido');
            $hosts[$host] = ($hosts[$host] ?? 0) + 1;
        }

        arsort($hosts);

        return collect($hosts)
            ->map(fn ($total, $host) => ['host' => $host, 'total' => $total])
            ->values();
    }
}
