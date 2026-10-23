<?php

namespace Modules\Academic\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaVirtualActivity;
use Modules\Academic\Entities\AcaVirtualCourse;
use Modules\Academic\Entities\AcaVirtualResource;
use Modules\Academic\Entities\AcaVirtualSubmission;
use Modules\Academic\Services\SchoolContextService;
use Modules\Academic\Services\StudentPortalAuth;
use Throwable;

/**
 * Portal del alumno del nivel Inicial.
 *
 * El acceso reutiliza la tabla de usuarios del sistema, pero solo deja entrar
 * a quien tiene una matricula ACTIVA del año escolar vigente en el nivel
 * Inicial. Los cursos, unidades y sesiones salen del aula virtual que
 * configura el administrador; el alumno solo puede abrir los de su sección.
 */
class ColegioInicialController extends ColegioPublicController
{
    public function __construct(
        private SchoolContextService $context,
        private StudentPortalAuth $studentAuth
    ) {
    }

    /**
     * Pantalla de bienvenida (GET). Si ya hay una sesion valida de Inicial,
     * se salta el formulario y entra directo al aula.
     */
    public function create()
    {
        if (Auth::check()) {
            [$portal] = $this->guard();

            if ($portal) {
                return redirect()->route('colegio.inicial.dashboard');
            }
        }

        return view('colegio.inicial.login', [
            'school' => $this->school(),
            'year' => now()->year,
            'openLogin' => session()->has('errors') || session()->has('status'),
        ]);
    }

    /**
     * Valida credenciales y la matricula antes de abrir la sesion.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'codigo' => ['required', 'string', 'max:60'],
                'clave' => ['required', 'string'],
            ],
            [
                'codigo.required' => 'Escribe tu código de alumno.',
                'clave.required' => 'Escribe tu contraseña.',
            ]
        );

        // Código de alumno + contraseña (DNI la primera vez). La matrícula del
        // nivel se revisa dentro del servicio, antes de abrir la sesión.
        $result = $this->studentAuth->authenticate($data['codigo'], $data['clave'], AcaSchoolLevel::INICIAL);

        if (isset($result['error'])) {
            return back()
                ->withInput($request->only('codigo'))
                ->withErrors(['codigo' => $result['error']]);
        }

        Auth::login($result['user'], $request->boolean('recordar'));
        $request->session()->regenerate();

        return redirect()
            ->route('colegio.inicial.dashboard')
            ->with('status', '¡Bienvenido, ' . $this->firstName($result['student']) . '!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('colegio.inicial.login')
            ->with('status', 'Cerraste sesión. ¡Te esperamos pronto!');
    }

    /**
     * Dashboard principal del alumno: sus cursos con unidades y sesiones.
     */
    public function dashboard()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.inicial.dashboard', [
            'portal' => $portal,
            'courses' => $portal['courses'],
            'messages' => $this->bubbleMessages($portal),
            'dockActive' => 'inicio',
        ]);
    }

    /**
     * Contenido de un curso: unidades, sesiones, materiales y actividades.
     */
    public function course(int $course)
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $model = $portal['courses']->firstWhere('id', $course);

        // Solo los cursos de la seccion del alumno son visibles, aunque
        // escriba el id de otro a mano.
        abort_if(! $model, 404);

        $model->load([
            'units' => fn ($query) => $query->where('status', true)->orderBy('sort_order')->orderBy('id'),
            'units.lessons' => fn ($query) => $query->where('status', true)->orderBy('sort_order')->orderBy('id'),
            'units.lessons.resources' => fn ($query) => $query->where('status', true)->orderBy('sort_order')->orderBy('id'),
            'units.resources' => fn ($query) => $query->where('status', true)->orderBy('sort_order')->orderBy('id'),
            'units.activities' => fn ($query) => $query->where('status', true)->orderBy('sort_order')->orderBy('id'),
            'resources' => fn ($query) => $query->whereNull('unit_id')->where('status', true)->orderBy('sort_order'),
        ]);

        return view('colegio.inicial.curso', [
            'portal' => $portal,
            'course' => $model,
            // Lista unica del curso: alimenta el modal de materiales de apoyo.
            'materials' => $this->courseMaterials($model),
            'messages' => $this->bubbleMessages($portal),
            'dockActive' => 'cursos',
        ]);
    }

    /**
     * Muestra un material dentro del aula (imagen o PDF en el visor del modal).
     */
    public function viewMaterial(int $resource)
    {
        return $this->sendMaterial($resource, false);
    }

    /**
     * Descarga un material con un nombre legible para el alumno.
     */
    public function downloadMaterial(int $resource)
    {
        return $this->sendMaterial($resource, true);
    }

    /**
     * Entrega el archivo de un material solo si pertenece a un curso del alumno.
     */
    private function sendMaterial(int $resourceId, bool $download)
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $resource = AcaVirtualResource::query()
            ->whereIn('course_id', $portal['courses']->pluck('id'))
            ->where('status', true)
            ->whereIn('visibility', ['all', 'students'])
            ->find($resourceId);

        $disk = Storage::disk('public');

        abort_if(! $resource || ! $resource->path || ! $disk->exists($resource->path), 404);

        $path = $disk->path($resource->path);
        $extension = mb_strtolower(pathinfo($resource->path, PATHINFO_EXTENSION));
        $name = Str::slug($resource->title ?: 'material') . ($extension ? '.' . $extension : '');

        // Solo se sirven dentro del visor los formatos que el navegador puede
        // mostrar; el resto se descarga para evitar contenido activo inline.
        $inlineMimes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
        ];

        if ($download || ! isset($inlineMimes[$extension])) {
            return response()->download($path, $name, ['X-Content-Type-Options' => 'nosniff']);
        }

        return response()->file($path, [
            'Content-Type' => $inlineMimes[$extension],
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Zona de juegos. Cinco tarjetas abren su juego real y el resto sigue
     * marcado como proximamente.
     */
    public function games()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.inicial.juegos', [
            'portal' => $portal,
            'messages' => $this->bubbleMessages($portal),
            'dockActive' => 'juegos',
        ]);
    }

    /**
     * Juego de rompecabezas. La partida corre en el navegador; el portal solo
     * comprueba, igual que el resto del aula, que el alumno tenga matricula.
     */
    public function puzzle()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.juegos.rompecabezas', [
            'portal' => $portal,
            'dockActive' => 'juegos',
        ]);
    }

    /**
     * Juego «Contando frutas»: diez niveles para contar del 1 al 10. La
     * partida se resuelve en el navegador y no necesita datos adicionales.
     */
    public function fruits()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.juegos.contando-frutas', [
            'portal' => $portal,
            'dockActive' => 'juegos',
        ]);
    }

    /**
     * Juego «Reconoce la letra»: diez niveles para reconocer el abecedario
     * español con la voz o el teclado. La partida se resuelve en el navegador.
     */
    public function letters()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.juegos.reconoce-letra', [
            'portal' => $portal,
            'dockActive' => 'juegos',
        ]);
    }

    /**
     * Juego «Colores y formas»: diez niveles para reconocer colores, figuras,
     * parejas, clasificación y secuencias. La partida se resuelve en el navegador.
     */
    public function colors()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.juegos.colores-formas', [
            'portal' => $portal,
            'dockActive' => 'juegos',
        ]);
    }

    /**
     * Juego «Memoria de animales»: diez niveles con sonidos y fotografías
     * reales de animales, y una mecánica de parejas. La configuración de los
     * animales y sus créditos vive en config/colegio_animales.php.
     */
    public function memory()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.juegos.memoria-animales', [
            'portal' => $portal,
            'dockActive' => 'juegos',
            'animals' => $this->animalGameAnimals(),
        ]);
    }

    /**
     * Animales del juego con las URLs reales de sus fotos y sonidos.
     *
     * @return array<int, array<string, mixed>>
     */
    private function animalGameAnimals(): array
    {
        return collect(config('colegio_animales.animals', []))
            ->map(function (array $animal): array {
                $animal['photo_url'] = asset($animal['photo']);
                $animal['audio_url'] = asset($animal['audio']);

                return $animal;
            })
            ->values()
            ->all();
    }

    /**
     * Cambio de contraseña desde «Mi perfil».
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $data = $request->validateWithBag('perfil', [
            'clave_actual' => ['required', 'string'],
            'clave_nueva' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'clave_actual.required' => 'Escribe tu contraseña actual.',
            'clave_nueva.required' => 'Escribe tu nueva contraseña.',
            'clave_nueva.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'clave_nueva.confirmed' => 'Las contraseñas nuevas no coinciden.',
        ]);

        $result = $this->studentAuth->changePassword(
            Auth::user(),
            $data['clave_actual'],
            $data['clave_nueva']
        );

        if (isset($result['error'])) {
            return back()->withErrors(['clave_actual' => $result['error']], 'perfil');
        }

        return back()->with('perfil_status', 'Tu contraseña se actualizó. Úsala la próxima vez que entres.');
    }

    /**
     * Datos listos para las vistas. Devuelve [portal, redireccion].
     */
    private function guard(): array
    {
        if (! Auth::check()) {
            return [null, redirect()->route('colegio.inicial.login')];
        }

        $membership = $this->studentAuth->membership(Auth::user(), AcaSchoolLevel::INICIAL);

        if (isset($membership['error'])) {
            return [null, redirect()->route('colegio.inicial.login')->withErrors(['codigo' => $membership['error']])];
        }

        return [$this->buildPortal($membership), null];
    }

    private function buildPortal(array $membership): array
    {
        /** @var AcaSchoolStudent $student */
        $student = $membership['student'];
        /** @var AcaSchoolEnrollment $enrollment */
        $enrollment = $membership['enrollment'];

        $school = $this->context->currentSchool();
        $person = $student->person;
        $section = $enrollment->section;
        $grade = $section?->grade;

        $courses = $this->studentCourses((int) $school->id, (int) $enrollment->year_id, (int) $enrollment->section_id);

        $courseIds = $courses->pluck('id');
        $activityIds = AcaVirtualActivity::whereIn('course_id', $courseIds)
            ->where('status', true)
            ->pluck('id');

        $completed = AcaVirtualSubmission::where('student_id', $student->id)
            ->whereIn('activity_id', $activityIds)
            ->count();

        $total = $activityIds->count();

        return [
            'school' => $this->school(),
            'student' => [
                'name' => $this->firstName($student) ?: 'Amiguito',
                'full_name' => $person?->full_name ?: $this->firstName($student),
                'code' => $student->student_code,
                'avatar_url' => $this->personImageUrl($person?->image),
            ],
            'grade' => [
                'name' => $grade?->name ?? 'Inicial',
                'section' => $section?->name,
                'level' => $grade?->level?->name ?? 'Inicial',
                'year' => $enrollment->year?->year,
                'label' => $this->gradeLabel($grade?->name, $section?->name),
            ],
            'progress' => [
                'level' => 1 + intdiv($completed, 5),
                'completed' => $completed,
                'total' => $total,
                'percent' => $total > 0 ? (int) round($completed * 100 / $total) : 0,
            ],
            'stats' => [
                'courses' => $courses->count(),
                'units' => $courses->sum(fn (AcaVirtualCourse $course) => $course->aula['units']),
                'sessions' => $courses->sum(fn (AcaVirtualCourse $course) => $course->aula['sessions']),
            ],
            'courses' => $courses,
        ];
    }

    /**
     * Cursos del aula virtual de la seccion del alumno en su año escolar.
     */
    private function studentCourses(int $schoolId, int $yearId, int $sectionId)
    {
        $courses = AcaVirtualCourse::with(['area', 'teacher', 'units.lessons', 'units.activities'])
            ->where('school_id', $schoolId)
            ->where('year_id', $yearId)
            ->where('section_id', $sectionId)
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $courses->each(function (AcaVirtualCourse $course, int $index) {
            $units = $course->units->where('status', true);
            $lessons = $units->flatMap(fn ($unit) => $unit->lessons->where('status', true));
            $activities = $units->flatMap(fn ($unit) => $unit->activities->where('status', true));

            $areaName = $course->area?->name ?? $course->name ?? 'Mi curso';
            $theme = $this->themeFor($areaName, $index);

            $course->setAttribute('aula', [
                'name' => $course->name ?: $areaName,
                'area' => $areaName,
                'theme' => $theme['key'],
                'tone' => $theme['tone'],
                'units' => $units->count(),
                'sessions' => $lessons->count(),
                'activities' => $activities->count(),
                'teacher' => $course->teacher?->full_name,
            ]);
        });

        return $courses;
    }

    /**
     * Icono y color de la tarjeta segun el area curricular.
     *
     * @return array{key: string, tone: string}
     */
    private function themeFor(string $areaName, int $index): array
    {
        $name = mb_strtolower($areaName);

        $themes = [
            ['needle' => ['matem', 'número', 'numero'], 'key' => 'numbers', 'tone' => 'orange'],
            ['needle' => ['comunic', 'desarrollo personal', 'lengua', 'lectura', 'inglés', 'ingles'], 'key' => 'book', 'tone' => 'blue'],
            ['needle' => ['ciencia', 'ambiente', 'tecnolog'], 'key' => 'leaf', 'tone' => 'green'],
            ['needle' => ['psicomotric', 'educación física', 'educacion fisica'], 'key' => 'ball', 'tone' => 'orange'],
            ['needle' => ['arte', 'cultura', 'dibujo', 'música', 'musica'], 'key' => 'pencil', 'tone' => 'lilac'],
            ['needle' => ['religio'], 'key' => 'heart', 'tone' => 'lilac'],
            ['needle' => ['social', 'personal', 'cívica', 'civica'], 'key' => 'globe', 'tone' => 'blue'],
        ];

        foreach ($themes as $theme) {
            foreach ($theme['needle'] as $needle) {
                if (str_contains($name, $needle)) {
                    return ['key' => $theme['key'], 'tone' => $theme['tone']];
                }
            }
        }

        $tones = ['green', 'blue', 'lilac', 'yellow'];

        return ['key' => 'star', 'tone' => $tones[$index % count($tones)]];
    }

    /**
     * Materiales visibles en el modal: los del curso, los de sus unidades y
     * los de cada sesion, sin repetir el mismo recurso.
     *
     * @return array<int, array<string, mixed>>
     */
    private function courseMaterials(AcaVirtualCourse $course): array
    {
        $seen = [];
        $materials = [];

        $push = function (AcaVirtualResource $resource, string $origin) use (&$seen, &$materials) {
            if (isset($seen[$resource->id])) {
                return;
            }

            $seen[$resource->id] = true;
            $materials[] = $this->materialPayload($resource, $origin);
        };

        foreach ($course->resources as $resource) {
            $push($resource, 'Material del curso');
        }

        foreach ($course->units as $unit) {
            foreach ($unit->resources as $resource) {
                $push($resource, 'Unidad · ' . $unit->title);
            }

            foreach ($unit->lessons as $lesson) {
                foreach ($lesson->resources as $resource) {
                    $push($resource, 'Sesión · ' . $lesson->title);
                }
            }
        }

        return $materials;
    }

    /**
     * Datos que usa el modal para previsualizar y descargar un material.
     *
     * @return array<string, mixed>
     */
    private function materialPayload(AcaVirtualResource $resource, string $origin): array
    {
        $mime = (string) $resource->mime_type;
        $extension = $resource->path ? mb_strtolower(pathinfo($resource->path, PATHINFO_EXTENSION)) : '';
        $hasFile = (bool) $resource->path;

        if (str_starts_with($mime, 'image/') || in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
            $kind = $hasFile ? 'image' : 'link';
        } elseif ($mime === 'application/pdf' || $extension === 'pdf') {
            $kind = $hasFile ? 'pdf' : 'link';
        } elseif (! $hasFile && $resource->type === 'embed') {
            $kind = 'embed';
        } elseif ($hasFile) {
            $kind = 'file';
        } else {
            $kind = 'link';
        }

        return [
            'id' => (int) $resource->id,
            'title' => $resource->title ?: 'Material',
            'kind' => $kind,
            'origin' => $origin,
            'size' => $resource->size_kb ? $this->formatSize((int) $resource->size_kb) : null,
            'preview_url' => $hasFile ? route('colegio.inicial.material.view', $resource->id) : null,
            'download_url' => $hasFile ? route('colegio.inicial.material.download', $resource->id) : null,
            'external_url' => $resource->url ?: null,
            'embed_html' => $resource->type === 'embed' ? $resource->content : null,
        ];
    }

    /**
     * Tamano en KB o MB con formato legible para el alumno.
     */
    private function formatSize(int $sizeKb): string
    {
        if ($sizeKb >= 1024) {
            return number_format($sizeKb / 1024, 1, ',', '.') . ' MB';
        }

        return $sizeKb . ' KB';
    }

    /**
     * Frases de la mascota. Solo usan datos reales del alumno y su aula.
     */
    private function bubbleMessages(array $portal): array
    {
        $name = $portal['student']['name'];
        $courses = $portal['courses'];

        $messages = ['¡Hola, ' . $name . '! Estoy feliz de verte por aquí.'];

        if ($courses->isNotEmpty()) {
            $first = $courses->first()->aula['name'];
            $messages[] = 'Hoy podemos empezar con «' . $first . '». ¿Te animas?';
        }

        if ($portal['stats']['sessions'] > 0) {
            $messages[] = 'Tu aula tiene ' . $portal['stats']['sessions'] . ' ' .
                ($portal['stats']['sessions'] === 1 ? 'sesión' : 'sesiones') . ' esperando por ti.';
        }

        $messages[] = 'Si necesitas ayuda, pide a tu profesora que te acompañe.';
        $messages[] = 'Recuerda descansar y tomar agua mientras aprendes.';

        return $messages;
    }

    /**
     * Etiqueta del aula. Algunas secciones ya se llaman «Aula Rojo», así que
     * solo se antepone la palabra cuando el nombre no la trae.
     */
    private function gradeLabel(?string $gradeName, ?string $sectionName): string
    {
        $gradeName = trim((string) ($gradeName ?: 'Inicial'));
        $sectionName = trim((string) $sectionName);

        if ($sectionName === '') {
            return $gradeName;
        }

        $sectionLabel = str_starts_with(mb_strtolower($sectionName), 'aula')
            ? $sectionName
            : 'Aula ' . $sectionName;

        return $gradeName . ' · ' . $sectionLabel;
    }

    /**
     * Solo el primer nombre: el saludo se lee mejor para los niños.
     */
    private function firstName(AcaSchoolStudent $student): ?string
    {
        $person = $student->person;
        $names = trim((string) ($person?->names ?: $person?->full_name));

        if ($names === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $names) ?: [$names];

        return \Illuminate\Support\Str::title($parts[0]);
    }

    private function personImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }
}
