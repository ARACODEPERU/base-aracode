<?php

namespace Modules\Academic\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Entities\AcaBrochure;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCourseLanding;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Onlineshop\Entities\OnliItem;

/**
 * Landing pública de un curso.
 *
 * La web del ERP guarda la landing en una fila JSON por curso. Este servicio
 * replica sus reglas para que el agente MCP pueda crearla desde cero y editar
 * sus secciones con los mismos nombres y estructuras que usa el mantenedor.
 */
class CourseLandingService
{
    /** Secciones editables y su columna real. */
    public const SECTIONS = [
        'professional' => 'professional_section',
        'staff' => 'staff_section',
        'results' => 'results_section',
        'testimonials' => 'testimonials_section',
        'study_plan' => 'study_plan_section',
        'problem' => 'problem_section',
        'investment' => 'investment_section',
        'faq' => 'faq_section',
    ];

    /** Campos simples de configuración. */
    private const SETTINGS = [
        'url_slug',
        'whatsapp_link',
        'flow_id',
        'payment_facilities_link',
        'corporate_contact_link',
        'is_published',
    ];

    public function __construct(private CourseAuthoringService $authoring)
    {
    }

    /**
     * Devuelve la landing existente o crea una con un slug único del curso.
     */
    public function ensure(AcaCourse $curso): AcaCourseLanding
    {
        $landing = AcaCourseLanding::where('course_id', $curso->id)->first();

        if ($landing) {
            return $landing;
        }

        $base = Str::slug($curso->description) ?: 'curso-' . $curso->id;

        return AcaCourseLanding::create([
            'course_id' => $curso->id,
            'url_slug' => $this->uniqueSlug($base),
            'banner_language' => 'es',
            'is_published' => false,
        ]);
    }

    /**
     * Estado completo de la landing para que el agente sepa qué falta antes de
     * escribir una sección o de pedirle datos a la persona.
     */
    public function state(AcaCourseLanding $landing): array
    {
        $landing->loadMissing('course.category', 'course.modality');
        $curso = $landing->course;

        $secciones = [];

        foreach (self::SECTIONS as $clave => $columna) {
            $valor = $landing->{$columna};
            $secciones[$clave] = is_array($valor) ? $valor : [];
        }

        $docentes = $this->courseTeachers($curso);
        $brochure = AcaBrochure::where('course_id', $landing->course_id)->first();

        return [
            'id' => $landing->id,
            'course_id' => $landing->course_id,
            'url_slug' => $landing->url_slug,
            'url' => $landing->url_slug ? url('curso/' . $landing->url_slug) : null,
            'preview_url' => route('landing_preview', $landing->id),
            'published' => (bool) $landing->is_published,
            'settings' => [
                'whatsapp_link' => $landing->whatsapp_link,
                'flow_id' => $landing->flow_id,
                'payment_facilities_link' => $landing->payment_facilities_link,
                'corporate_contact_link' => $landing->corporate_contact_link,
                'banner_start_date' => $landing->banner_start_date?->format('Y-m-d'),
                'banner_end_date' => $landing->banner_end_date?->format('Y-m-d'),
                'banner_duration' => $landing->banner_duration,
                'banner_language' => $landing->banner_language,
                'banner_video_link' => $landing->banner_video_link,
            ],
            'course' => [
                'title' => $curso?->description,
                'image' => $curso?->image,
                'image_url' => $curso?->image ? asset('storage/' . $curso->image) : null,
                'category' => $curso?->category?->description,
                'modality' => $curso?->modality?->description,
                'price' => $curso?->price,
            ],
            'teachers' => $docentes,
            'sections' => $secciones,
            'brochure' => [
                'blocks' => $this->brochureState($brochure),
                'file' => $brochure?->path_file,
            ],
            'missing' => $this->missing($landing, $curso, $docentes, $brochure),
        ];
    }

    /**
     * Crea o actualiza la landing. Solo cambia las claves enviadas; así una
     * llamada para editar una sección no borra las demás.
     */
    public function update(AcaCourse $curso, array $datos): AcaCourseLanding
    {
        $landing = $this->ensure($curso);
        $cambios = [];

        foreach (self::SETTINGS as $campo) {
            if (array_key_exists($campo, $datos)) {
                $cambios[$campo] = $campo === 'is_published'
                    ? (bool) $datos[$campo]
                    : $datos[$campo];
            }
        }

        if (array_key_exists('url_slug', $cambios)) {
            $cambios['url_slug'] = $this->normalizeSlug((string) $cambios['url_slug'], $landing->id);
        }

        if (array_key_exists('banner_start_date', $datos) || array_key_exists('banner_end_date', $datos)) {
            $inicio = array_key_exists('banner_start_date', $datos)
                ? $datos['banner_start_date']
                : $landing->banner_start_date;

            $fin = array_key_exists('banner_end_date', $datos)
                ? $datos['banner_end_date']
                : $landing->banner_end_date;

            if (blank($inicio) && blank($fin)) {
                $cambios['banner_start_date'] = null;
                $cambios['banner_end_date'] = null;
                $cambios['banner_duration'] = null;
            } elseif (blank($inicio) || blank($fin)) {
                throw ValidationException::withMessages([
                    'banner_start_date' => 'Indica la fecha de inicio y la de fin de la landing.',
                ]);
            } else {
                $inicioCarbon = Carbon::parse($inicio);
                $finCarbon = Carbon::parse($fin);

                if ($finCarbon->lt($inicioCarbon)) {
                    throw ValidationException::withMessages([
                        'banner_end_date' => 'La fecha de fin no puede ser anterior a la de inicio.',
                    ]);
                }

                $cambios['banner_start_date'] = $inicioCarbon->format('Y-m-d');
                $cambios['banner_end_date'] = $finCarbon->format('Y-m-d');
                $cambios['banner_duration'] = $inicioCarbon->diffInDays($finCarbon) + 1;
            }
        }

        if (array_key_exists('banner_video_link', $datos)) {
            $cambios['banner_video_link'] = $this->normalizeBannerVideo($datos['banner_video_link']);
        }

        if (array_key_exists('banner_language', $datos)) {
            $idioma = (string) $datos['banner_language'];

            if (! in_array($idioma, ['es', 'en', 'zh'], true)) {
                throw ValidationException::withMessages([
                    'banner_language' => 'El idioma debe ser es, en o zh.',
                ]);
            }

            $cambios['banner_language'] = $idioma;
        }

        foreach (self::SECTIONS as $clave => $columna) {
            if (array_key_exists($clave, $datos)) {
                $cambios[$columna] = $datos[$clave] === null
                    ? null
                    : $this->normalizeSection($clave, (array) $datos[$clave], $curso);
            }
        }

        if (array_key_exists('utm_config', $datos)) {
            $cambios['utm_config'] = $this->normalizeUtm((array) $datos['utm_config']);
        }

        if ($cambios === []) {
            throw ValidationException::withMessages([
                'landing' => 'No enviaste ningún ajuste ni sección de la landing.',
            ]);
        }

        $landing->update($cambios);

        return $landing->fresh();
    }

    /**
     * Guarda el PDF del brochure con la misma validación de firma que los
     * materiales de clase.
     */
    public function setBrochureFile(AcaCourse $curso, array $datos): AcaBrochure
    {
        return $this->authoring->setBrochureFile($curso, $datos);
    }

    /**
     * Normaliza una sección según su estructura real en la web.
     */
    private function normalizeSection(string $clave, array $valor, AcaCourse $curso): array
    {
        return match ($clave) {
            'professional' => $this->normalizeCards($valor, 'Actualización Profesional'),
            'results' => $this->normalizeCards($valor, 'Resultados del Programa'),
            'problem' => $this->normalizeCards($valor, 'El Problema'),
            'staff' => $this->normalizeStaff($valor),
            'testimonials' => $this->normalizeTestimonials($valor, $curso),
            'study_plan' => $this->normalizeStudyPlan($valor, $curso),
            'investment' => $this->normalizeInvestment($valor, $curso),
            'faq' => $this->normalizeFaq($valor),
            default => [],
        };
    }

    /**
     * Secciones de tarjetas: actualización profesional, resultados y problema.
     */
    private function normalizeCards(array $valor, string $nombreDefecto): array
    {
        $items = [];

        foreach (($valor['items'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $items[] = [
                'icon' => $this->cleanText($item['icon'] ?? '', 'items.icon', 100),
                'title' => $this->cleanText($item['title'] ?? '', 'items.title', 255),
                'description' => $this->cleanText($item['description'] ?? '', 'items.description', 5000),
            ];
        }

        return [
            'name' => $this->cleanText($valor['name'] ?? $nombreDefecto, 'name', 255) ?: $nombreDefecto,
            'title' => $this->cleanText($valor['title'] ?? '', 'title', 300),
            'description' => $this->cleanText($valor['description'] ?? '', 'description', 10000),
            'items' => $items,
        ];
    }

    /**
     * Staff: los docentes se validan y se completan con los datos de la BD.
     */
    private function normalizeStaff(array $valor): array
    {
        $entradas = [];

        if (isset($valor['teacher_ids']) && is_array($valor['teacher_ids'])) {
            foreach ($valor['teacher_ids'] as $teacherId) {
                $entradas[] = ['teacher_id' => (int) $teacherId];
            }
        } else {
            foreach (($valor['teachers'] ?? []) as $item) {
                if (is_array($item) && isset($item['teacher_id'])) {
                    $entradas[] = $item;
                }
            }
        }

        $ids = array_values(array_unique(array_map(
            fn (array $item) => (int) $item['teacher_id'],
            $entradas
        )));

        $docentes = AcaTeacher::with('person')->whereIn('id', $ids)->get()->keyBy('id');

        if ($docentes->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'staff.teachers' => 'Uno o más docentes del staff no existen. Usa academic_list_teachers.',
            ]);
        }

        $teachers = [];

        foreach ($entradas as $entrada) {
            $docente = $docentes[(int) $entrada['teacher_id']];
            $persona = $docente->person;

            $nombre = $entrada['teacher_names'] ?? null;
            $ocupacion = $entrada['teacher_ocupation'] ?? null;

            if (blank($nombre)) {
                $nombre = $persona?->formatted_name ?? $persona?->full_name ?? 'Docente';
            }

            if (blank($ocupacion)) {
                $ocupacion = $persona?->ocupacion ?? 'Instructor';
            }

            $teachers[] = [
                'teacher_id' => $docente->id,
                'teacher_names' => $this->cleanText($nombre, 'staff.teachers.names', 255),
                'teacher_ocupation' => $this->cleanText($ocupacion, 'staff.teachers.ocupation', 255),
                'selected' => true,
            ];
        }

        return [
            'name' => $this->cleanText($valor['name'] ?? 'Nuestro Staff', 'staff.name', 255) ?: 'Nuestro Staff',
            'title' => $this->cleanText($valor['title'] ?? '', 'staff.title', 300),
            'description' => $this->cleanText($valor['description'] ?? '', 'staff.description', 10000),
            'teachers' => $teachers,
        ];
    }

    /**
     * Testimonios, con imagen opcional descargada al disco público.
     */
    private function normalizeTestimonials(array $valor, AcaCourse $curso): array
    {
        $items = [];

        foreach (($valor['items'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $nombre = $this->cleanText($item['name'] ?? '', 'testimonials.items.name', 255);

            if ($nombre === '') {
                throw ValidationException::withMessages([
                    'testimonials.items.name' => 'Cada testimonio necesita un nombre.',
                ]);
            }

            $items[] = [
                'name' => $nombre,
                'presentation' => $this->cleanText($item['presentation'] ?? '', 'testimonials.items.presentation', 255),
                'description' => $this->cleanText($item['description'] ?? '', 'testimonials.items.description', 10000),
                'image' => $this->imageFromItem($item, 'testimonials/' . $curso->id),
            ];
        }

        return [
            'name' => $this->cleanText($valor['name'] ?? 'Testimonios', 'testimonials.name', 255) ?: 'Testimonios',
            'title' => $this->cleanText($valor['title'] ?? '', 'testimonials.title', 300),
            'description' => $this->cleanText($valor['description'] ?? '', 'testimonials.description', 10000),
            'items' => $items,
        ];
    }

    /**
     * Plan de estudios, con imagen opcional y numeración de los temas.
     */
    private function normalizeStudyPlan(array $valor, AcaCourse $curso): array
    {
        $items = [];

        foreach (array_values($valor['items'] ?? []) as $indice => $item) {
            if (! is_array($item)) {
                continue;
            }

            $titulo = $this->cleanText($item['title'] ?? '', 'study_plan.items.title', 255);

            if ($titulo === '') {
                throw ValidationException::withMessages([
                    'study_plan.items.title' => 'Cada tema del plan de estudios necesita un título.',
                ]);
            }

            $items[] = [
                'number' => isset($item['number']) ? (int) $item['number'] : $indice + 1,
                'title' => $titulo,
                'description' => $this->cleanText($item['description'] ?? '', 'study_plan.items.description', 10000),
            ];
        }

        return [
            'name' => $this->cleanText($valor['name'] ?? 'Plan de Estudios', 'study_plan.name', 255) ?: 'Plan de Estudios',
            'title' => $this->cleanText($valor['title'] ?? '', 'study_plan.title', 300),
            'description' => $this->cleanText($valor['description'] ?? '', 'study_plan.description', 10000),
            'image' => $this->imageFromItem($valor, 'study_plan/' . $curso->id),
            'items' => $items,
        ];
    }

    /**
     * Inversión: dos planes en la web, con precio actual sincronizado al
     * carrito cuando existe un OnliItem del curso.
     */
    private function normalizeInvestment(array $valor, AcaCourse $curso): array
    {
        $items = [];

        foreach (array_values($valor['items'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $items[] = [
                'tag' => $this->cleanText($item['tag'] ?? '', 'investment.items.tag', 100),
                'title' => $this->cleanText($item['title'] ?? '', 'investment.items.title', 255),
                'description' => $this->cleanText($item['description'] ?? '', 'investment.items.description', 10000),
                'price_before' => $this->cleanText($item['price_before'] ?? '', 'investment.items.price_before', 50),
                'price_before_text' => $this->cleanText($item['price_before_text'] ?? '', 'investment.items.price_before_text', 50),
                'price_before_visible' => (bool) ($item['price_before_visible'] ?? false),
                'price_now' => $this->cleanText($item['price_now'] ?? '', 'investment.items.price_now', 50),
                'price_now_text' => $this->cleanText($item['price_now_text'] ?? '', 'investment.items.price_now_text', 50),
                'features' => array_values(array_filter(array_map(
                    fn ($feature) => $this->cleanText($feature, 'investment.items.features', 255),
                    (array) ($item['features'] ?? [])
                ))),
            ];
        }

        if (isset($items[0]['price_now']) && is_numeric($items[0]['price_now'])) {
            $onliItem = OnliItem::where('item_id', $curso->id)->first();

            if ($onliItem) {
                $onliItem->update(['price' => (float) $items[0]['price_now']]);
            }
        }

        return [
            'name' => $this->cleanText($valor['name'] ?? 'Inversión', 'investment.name', 255) ?: 'Inversión',
            'title' => $this->cleanText($valor['title'] ?? '', 'investment.title', 300),
            'description' => $this->cleanText($valor['description'] ?? '', 'investment.description', 10000),
            'items' => $items,
        ];
    }

    /**
     * Preguntas frecuentes ordenables y visibles.
     */
    private function normalizeFaq(array $valor): array
    {
        $items = [];

        foreach (array_values($valor['items'] ?? []) as $indice => $item) {
            if (! is_array($item)) {
                continue;
            }

            $pregunta = $this->cleanText($item['question'] ?? '', 'faq.items.question', 500);

            if ($pregunta === '') {
                throw ValidationException::withMessages([
                    'faq.items.question' => 'Cada pregunta frecuente necesita un texto.',
                ]);
            }

            $items[] = [
                'question' => $pregunta,
                'answer' => $this->cleanText($item['answer'] ?? '', 'faq.items.answer', 10000),
                'sort' => isset($item['sort']) ? (int) $item['sort'] : $indice + 1,
                'visible' => (bool) ($item['visible'] ?? true),
            ];
        }

        return [
            'name' => $this->cleanText($valor['name'] ?? 'Preguntas Frecuentes', 'faq.name', 255) ?: 'Preguntas Frecuentes',
            'title' => $this->cleanText($valor['title'] ?? '', 'faq.title', 300),
            'description' => $this->cleanText($valor['description'] ?? '', 'faq.description', 10000),
            'items' => $items,
        ];
    }

    /**
     * Configuración de campaña y canales UTM.
     */
    private function normalizeUtm(array $valor): array
    {
        $channels = [];

        foreach (($valor['channels'] ?? []) as $canal) {
            if (! is_array($canal) || blank($canal['id'] ?? null)) {
                continue;
            }

            $channels[] = [
                'id' => $this->cleanText($canal['id'], 'utm_config.channels.id', 100),
                'term' => $this->cleanText($canal['term'] ?? '', 'utm_config.channels.term', 500),
                'content' => $this->cleanText($canal['content'] ?? '', 'utm_config.channels.content', 500),
                'active' => (bool) ($canal['active'] ?? false),
            ];
        }

        return [
            'campaign' => $this->cleanText($valor['campaign'] ?? '', 'utm_config.campaign', 255),
            'channels' => $channels,
        ];
    }

    /**
     * Acepta una URL de YouTube/Vimeo, un iframe ya armado o una URL de embed.
     * Guarda siempre un iframe coherente con el resto de la web.
     */
    private function normalizeBannerVideo(mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        if (stripos($valor, '<iframe') !== false) {
            if (! preg_match('/src\s*=\s*["\']([^"\']+)/i', $valor, $coincidencia)) {
                throw ValidationException::withMessages([
                    'banner_video_link' => 'El iframe no tiene un src válido.',
                ]);
            }

            $valor = html_entity_decode($coincidencia[1], ENT_QUOTES, 'UTF-8');
        }

        $host = strtolower((string) parse_url($valor, PHP_URL_HOST));

        $esYoutube = $host === 'youtube.com'
            || str_ends_with($host, '.youtube.com')
            || $host === 'youtu.be'
            || str_ends_with($host, '.youtu.be');

        if ($esYoutube) {
            return $this->authoring->videoEmbed('youtube', $valor);
        }

        if ($host === 'vimeo.com' || str_ends_with($host, '.vimeo.com')) {
            return $this->authoring->videoEmbed('vimeo', $valor);
        }

        throw ValidationException::withMessages([
            'banner_video_link' => 'El video del banner debe ser un enlace o iframe de YouTube o Vimeo.',
        ]);
    }

    /**
     * Descarga/guarda una imagen de una sección. Si el valor ya es una ruta del
     * disco público, se conserva.
     */
    private function imageFromItem(array $item, string $carpeta): ?string
    {
        foreach (['image_base64', 'image_url'] as $campo) {
            if (filled($item[$campo] ?? null)) {
                return $this->authoring->storeLandingImage((string) $item[$campo], $carpeta);
            }
        }

        $imagen = trim((string) ($item['image'] ?? ''));

        if ($imagen === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $imagen)) {
            return $this->authoring->storeLandingImage($imagen, $carpeta);
        }

        if (! str_starts_with($imagen, 'uploads/')) {
            throw ValidationException::withMessages([
                'image' => 'La imagen debe ser una ruta de uploads/ o una URL https pública.',
            ]);
        }

        return $imagen;
    }

    /**
     * Texto plano de las secciones: se eliminan etiquetas para que una landing
     * editada por un agente no inyecte HTML fuera de los campos previstos.
     */
    private function cleanText(mixed $valor, string $campo, int $max): string
    {
        $texto = (string) preg_replace('#<br\s*/?>#i', "\n", (string) $valor);
        $texto = trim(strip_tags($texto));

        if (mb_strlen($texto) > $max) {
            throw ValidationException::withMessages([
                $campo => 'El texto supera el máximo de ' . $max . ' caracteres.',
            ]);
        }

        return $texto;
    }

    /**
     * Slug único, con acentos y espacios normalizados.
     */
    private function normalizeSlug(string $slug, ?int $landingId = null): string
    {
        $slug = Str::slug($slug);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'url_slug' => 'El slug no puede quedar vacío.',
            ]);
        }

        if (mb_strlen($slug) > 120) {
            throw ValidationException::withMessages([
                'url_slug' => 'El slug admite como máximo 120 caracteres.',
            ]);
        }

        $existe = AcaCourseLanding::where('url_slug', $slug)
            ->when($landingId, fn ($query) => $query->where('id', '!=', $landingId))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'url_slug' => 'Ese slug ya lo usa otra landing.',
            ]);
        }

        return $slug;
    }

    /**
     * Slug libre a partir de un texto base.
     */
    private function uniqueSlug(string $base): string
    {
        $base = Str::limit(Str::slug($base) ?: 'curso', 120, '');
        $slug = $base;
        $numero = 2;

        while (AcaCourseLanding::where('url_slug', $slug)->exists()) {
            $slug = $base . '-' . $numero;
            $numero++;
        }

        return $slug;
    }

    /**
     * Docentes asignados al curso, con el principal marcado.
     *
     * @return array<int, array{id:int, name:string, occupation:?string, main:bool}>
     */
    private function courseTeachers(?AcaCourse $curso): array
    {
        if (! $curso) {
            return [];
        }

        $asignados = DB::table('aca_teacher_courses')
            ->where('course_id', $curso->id)
            ->pluck('teacher_id')
            ->all();

        $ids = array_values(array_unique(array_filter(array_map('intval', $asignados))));
        $docentes = AcaTeacher::with('person')->whereIn('id', $ids)->get();

        return $docentes->map(fn (AcaTeacher $docente) => [
            'id' => $docente->id,
            'name' => $docente->person?->full_name ?? 'Docente sin nombre',
            'occupation' => $docente->person?->ocupacion,
            'main' => (int) $curso->teacher_id === (int) $docente->id,
        ])->values()->all();
    }

    /**
     * Bloques de texto del brochure que ya están escritos.
     */
    private function brochureState(?AcaBrochure $brochure): array
    {
        if (! $brochure) {
            return [];
        }

        return [
            'resolution' => filled($brochure->resolution),
            'presentation' => filled($brochure->presentation),
            'benefits' => filled($brochure->benefits),
            'frequent_questions' => filled($brochure->frequent_questions),
            'curriculum_plan' => filled($brochure->curriculum_plan),
        ];
    }

    /**
     * Lista de datos que faltan para una landing completa.
     *
     * @param array<int, array{id:int, name:string, occupation:?string, main:bool}> $docentes
     * @return array<int, string>
     */
    private function missing(AcaCourseLanding $landing, ?AcaCourse $curso, array $docentes, ?AcaBrochure $brochure): array
    {
        $faltan = [];

        if (blank($landing->url_slug)) {
            $faltan[] = 'url_slug';
        }

        if (blank($landing->banner_start_date) || blank($landing->banner_end_date)) {
            $faltan[] = 'fechas_del_banner';
        }

        if (blank($curso?->image)) {
            $faltan[] = 'imagen_de_portada';
        }

        if ($docentes === []) {
            $faltan[] = 'docentes_del_curso';
        }

        foreach (self::SECTIONS as $clave => $columna) {
            $valor = $landing->{$columna};

            if (! $this->sectionHasContent($clave, $valor)) {
                $faltan[] = $clave;
            }
        }

        if (! $brochure || blank($brochure->path_file)) {
            $faltan[] = 'brochure_pdf';
        }

        return array_values(array_unique($faltan));
    }

    /**
     * Una sección cuenta como completa cuando tiene contenido real, no solo
     * el nombre por defecto que agrega el mantenedor.
     */
    private function sectionHasContent(string $clave, mixed $valor): bool
    {
        if (! is_array($valor) || $valor === []) {
            return false;
        }

        return match ($clave) {
            'professional', 'results', 'problem' => collect($valor['items'] ?? [])
                ->contains(fn ($item) => is_array($item) && filled($item['title'] ?? null)),
            'staff' => filled($valor['teachers'] ?? []),
            'testimonials', 'investment', 'faq' => filled($valor['items'] ?? []),
            'study_plan' => filled($valor['items'] ?? []) || filled($valor['image'] ?? null),
            default => true,
        };
    }
}
