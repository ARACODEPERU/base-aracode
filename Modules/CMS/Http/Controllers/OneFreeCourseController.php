<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Barryvdh\DomPDF\Facade\Pdf;
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
use Modules\CMS\Support\FreeCourseTopics;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            // Enlace publico del landing: se muestra en el panel para copiarlo y
            // es la base de todos los enlaces UTM.
            'publicUrl' => route('cms_free_course_landing', $id),
            // Vista previa del mismo landing desde el panel: no guarda nada.
            'previewUrl' => route('cms_landing_course_free_preview', $id),
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
        $campaign = $this->campaignForLanding($landing);

        $subscribers = $this->leadsQuery($landing, $start, $end)
            ->select('traffic_source', DB::raw('COUNT(*) as total'))
            ->groupBy('traffic_source')
            ->orderByDesc('total')
            ->get();

        $socialSubscribers = $this->leadsQuery($landing, $start, $end)
            ->where('traffic_source', 'social')
            ->select('utm_source', 'utm_medium', DB::raw('COUNT(*) as total'))
            ->groupBy('utm_source', 'utm_medium')
            ->orderByDesc('total')
            ->get();

        $referrerSubscribers = $this->groupByRefererHost(
            $this->leadsQuery($landing, $start, $end)
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
     * Campaña UTM configurada del landing (o la de por defecto).
     */
    private function campaignForLanding(?CmsLanding $landing): string
    {
        $config = $landing ? ($landing->utm_config ?? []) : [];

        return ! empty($config['campaign'])
            ? $config['campaign']
            : $this->defaultUtmCampaign($landing);
    }

    /**
     * Consulta base de los registros del landing: los envios con su campaña UTM
     * o los hechos desde su url (landing_url). Admite fechas y busqueda.
     */
    private function leadsQuery(?CmsLanding $landing, $start = null, $end = null, ?string $search = null)
    {
        $campaign = $this->campaignForLanding($landing);
        $slug = trim((string) ($landing->slug ?? ''));

        $query = CmsSubscriber::query()->where(function ($w) use ($campaign, $slug) {
            $w->where('utm_campaign', $campaign);

            // Tambien cuentan los envios hechos desde este landing sin UTM:
            // el formulario guarda la URL de origen en landing_url.
            if ($slug !== '') {
                $w->orWhere('landing_url', 'like', '%' . $slug . '%');
            }
        });

        if ($start && $end) {
            $query->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end);
        }

        $search = trim((string) $search);

        if ($search !== '') {
            $term = '%' . $search . '%';

            $query->where(function ($w) use ($term) {
                $w->where('full_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        return $query;
    }

    /**
     * Columnas que se muestran, exportan e imprimen de cada registro.
     */
    private function registroColumns(): array
    {
        return [
            'id', 'created_at', 'full_name', 'email', 'phone', 'subject', 'interests',
            'utm_source', 'utm_medium', 'utm_campaign', 'traffic_source', 'landing_url',
        ];
    }

    /**
     * Registros del landing paginados para la tabla del editor.
     */
    public function getRegistros(Request $request, $id): JsonResponse
    {
        $landing = CmsLanding::where('menu_id', $id)->first();

        [$start, $end] = $this->parseDateRange($request->get('dates'));

        $porPagina = 15;
        $pagina = max(1, (int) $request->get('page', 1));

        $query = $this->leadsQuery($landing, $start, $end, $request->get('search'));
        $total = (int) (clone $query)->count();

        $registros = $query->orderByDesc('id')
            ->forPage($pagina, $porPagina)
            ->get($this->registroColumns())
            ->map(fn ($r) => [
                'id' => $r->id,
                'fecha' => optional($r->created_at)->format('d/m/Y H:i'),
                'nombre' => $r->full_name,
                'email' => $r->email,
                'telefono' => $r->phone,
                'asunto' => $r->subject,
                // Temas de interes elegidos en el paso 2, con su etiqueta visible.
                'temas' => implode(', ', FreeCourseTopics::etiquetas($r->interests ?? [])),
                'utm_source' => $r->utm_source,
                'utm_medium' => $r->utm_medium,
                'utm_campaign' => $r->utm_campaign,
                'traffic_source' => $r->traffic_source,
                'landing_url' => $r->landing_url,
            ]);

        return response()->json([
            'data' => $registros,
            'total' => $total,
            'page' => $pagina,
            'per_page' => $porPagina,
            'last_page' => max(1, (int) ceil($total / $porPagina)),
            'campaign' => $this->campaignForLanding($landing),
        ]);
    }

    /**
     * Descarga en Excel (xlsx) los registros del landing con los filtros actuales.
     */
    public function exportRegistrosExcel(Request $request, $id): StreamedResponse
    {
        $landing = CmsLanding::where('menu_id', $id)->first();

        [$start, $end] = $this->parseDateRange($request->get('dates'));

        $registros = $this->leadsQuery($landing, $start, $end, $request->get('search'))
            ->orderByDesc('id')
            ->get($this->registroColumns());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registros');

        $cabeceras = [
            'Fecha', 'Nombre', 'Email', 'Teléfono', 'Curso(s) gratis', 'Temas de interés',
            'Origen', 'UTM source', 'UTM medium', 'UTM campaign', 'URL del landing',
        ];

        $sheet->fromArray($cabeceras, null, 'A1');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE9EEF5');

        $fila = 2;

        foreach ($registros as $registro) {
            $sheet->fromArray([
                optional($registro->created_at)->format('d/m/Y H:i'),
                $registro->full_name,
                $registro->email,
                $registro->phone,
                $registro->subject,
                implode(', ', FreeCourseTopics::etiquetas($registro->interests ?? [])),
                $registro->traffic_source,
                $registro->utm_source,
                $registro->utm_medium,
                $registro->utm_campaign,
                $registro->landing_url,
            ], null, 'A' . $fila);

            $fila++;
        }

        foreach (['A' => 18, 'B' => 30, 'C' => 32, 'D' => 18, 'E' => 28, 'F' => 45, 'G' => 16, 'H' => 16, 'I' => 16, 'J' => 26, 'K' => 45] as $columna => $ancho) {
            $sheet->getColumnDimension($columna)->setWidth($ancho);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'registros_landing_' . preg_replace('/[^a-z0-9]+/i', '_', $id) . '_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Descarga en PDF los registros del landing con los filtros actuales.
     */
    public function exportRegistrosPdf(Request $request, $id)
    {
        $landing = CmsLanding::where('menu_id', $id)->first();

        [$start, $end] = $this->parseDateRange($request->get('dates'));

        $registros = $this->leadsQuery($landing, $start, $end, $request->get('search'))
            ->orderByDesc('id')
            ->get($this->registroColumns());

        $pdf = Pdf::loadView('cms::landings.registros_pdf', [
            'landing' => $landing,
            'registros' => $registros,
            'campaign' => $this->campaignForLanding($landing),
            'desde' => $start,
            'hasta' => $end,
            'search' => $request->get('search'),
        ]);

        $pdf->setPaper('A4', 'landscape');

        $fileName = 'registros_landing_' . preg_replace('/[^a-z0-9]+/i', '_', $id) . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
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
