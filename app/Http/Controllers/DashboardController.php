<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentHistory;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaThemeComment;
use Modules\Blog\Entities\BlogArticle;
use Modules\Blog\Entities\BlogCategory;
use Modules\Onlineshop\Entities\OnliItem;

class DashboardController extends Controller
{
    protected $P000009;

    public function __construct()
    {
        $this->P000009 = Parameter::where('parameter_code', 'P000009')->value('value_default');
    }
    public function index()
    {
        if (Auth::user()->hasRole('Alumno')) {
            return Inertia::render('Academic::Dashboard/knowledge-base', [
                'interests' => $this->getDataStudent(),
                'expiring'  => $this->getExpiringItems()
            ]);

        } else {
            $person = Person::where('id', Auth::user()->person_id)->with('district')->first();
            return Inertia::render('Dashboard', [
                'authPerson' => $person,
                'P000009' => $this->P000009
            ]);
        }
    }

    public function getExpiringItems()
    {
        $student = AcaStudent::where('person_id', Auth::user()->person_id)->first();
        $studentId = $student->id;

        // --- Cursos por vencer ---
        $coursesExpiring = AcaCapRegistration::where('student_id', $studentId)
            ->with('course')
            ->where('unlimited', false)
            ->whereNotNull('date_end')
            ->whereDate('date_end', '>=', today())
            ->whereDate('date_end', '<=', today()->addDays(5))
            ->get()
            ->map(function ($item) {
                $end = Carbon::parse($item->date_end);
                $item->days_left = today()->diffInDays($end, false); // días restantes
                return $item;
            });

        // --- Suscripciones por vencer o vencidas ---
        $subscriptionsExpiring = AcaStudentSubscription::where('student_id', $studentId)
            ->where(function ($q) {
                $q->where('status', false)
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('date_end')
                        ->whereDate('date_end', '>=', today())
                        ->whereDate('date_end', '<=', today()->addDays(5));
                });
            })
            ->get()
            ->map(function ($item) {
                if ($item->date_end) {
                    $end = Carbon::parse($item->date_end);
                    $item->days_left = today()->greaterThan($end)
                        ? 0
                        : today()->diffInDays($end, false);
                } else {
                    $item->days_left = null;
                }
                return $item;
            });

        return [
            'courses' => $coursesExpiring,
            'subscriptions' => $subscriptionsExpiring,
        ];
    }

    private function getDataStudent()
    {
        $categoriesArticles = BlogCategory::withCount('articles')
            ->orderBy('articles_count', 'desc')
            ->limit(3)
            ->get();

        $student = AcaStudent::where('person_id', Auth::user()->person_id)->first();
        $student_id = $student->id;

        $popularCourses = AcaCourse::join('aca_teachers', 'aca_courses.teacher_id', 'aca_teachers.id')
            ->join('people', 'aca_teachers.person_id', 'people.id')
            ->join('onli_items', function ($query) {
                $query->on('onli_items.item_id', 'aca_courses.id')
                    ->where('entitie', 'Modules-Academic-Entities-AcaCourse');
            })
            ->select(
                'onli_items.id AS onitem_id',
                'onli_items.price AS onitem_price',
                'aca_courses.*',
                'aca_courses.slug AS slug',
                'people.names AS person_names',
                'people.father_lastname',
                'people.image AS person_image',
            )
            ->selectRaw('(SELECT COUNT(aca_cap_registrations.id) FROM aca_cap_registrations WHERE aca_cap_registrations.course_id = aca_courses.id) AS registrations')
            ->whereNotIn('aca_courses.id', function ($query) use ($student_id) {
                $query->select('i.course_id')
                    ->from('aca_cap_registrations AS i')
                    ->where('student_id', $student_id);
            })
            ->with('landing:id,course_id,url_slug,is_published')
            ->orderByDesc('registrations')
            ->take(8)
            ->get();

        // Destino del boton "Ingresar" de cada curso popular:
        // 1) Si el alumno ya tiene acceso (matricula, suscripcion, VIP o curso
        //    gratis) se entra directo a los modulos del curso.
        // 2) Si no, se ofrece la landing publica (/curso/{slug}) solo cuando
        //    esta activa (publicada y curso activo).
        // 3) En cualquier otro caso se ofrece la descripcion (/curso-descripcion/{slug}).
        $accessChecker = app(\Modules\Academic\Http\Controllers\AcaStudentController::class);
        $popularCourses->each(function ($course) use ($student_id, $accessChecker) {
            $hasAccess = $student_id
                ? $accessChecker->checkCourseAccess((int) $student_id, (int) $course->id)
                : false;

            $landing = $course->landing;
            $hasActiveLanding = $landing
                && $landing->is_published
                && filled($landing->url_slug)
                && (bool) $course->status;

            if ($hasAccess) {
                $course->entry_url = route('aca_mycourses_lessons', $course->id);
            } elseif ($hasActiveLanding) {
                $course->entry_url = route('course_url_slug', $landing->url_slug);
            } else {
                $course->entry_url = route('web_curso_descripcion', $course->slug ?: $course->onitem_id);
            }

            $course->has_access = $hasAccess;
            $course->unsetRelation('landing');
        });

        $articles = BlogArticle::orderByDesc('views')->take('4')->get();

        $lastCourse = AcaCapRegistration::with('course.modality')
            ->where('student_id', $student_id)
            ->latest()
            ->first();

        if ($lastCourse) {
            // Formatear la fecha en español con la primera letra en mayúscula
            $lastCourse->formatted_date = $lastCourse->created_at
                ? ucfirst(Carbon::parse($lastCourse->created_at)->translatedFormat('d M Y'))
                : null;

            // Inicializar valores por defecto
            $lastCourse->total_contents = 0;
            $lastCourse->total_activity = 0;
            $lastCourse->total_coments = 0;

            // Verificar si el curso existe antes de acceder a sus propiedades
            if ($lastCourse->course && isset($lastCourse->course->id)) {
                $courseId = $lastCourse->course->id;

                // Total de contenidos
                $lastCourse->total_contents = AcaContent::whereHas('theme.module.course', function ($query) use ($courseId) {
                    $query->where('id', $courseId);
                })->count();

                // Total de actividades del estudiante
                $lastCourse->total_activity = AcaStudentHistory::where('person_id', Auth::user()->person_id)
                    ->where('course_id', $courseId)
                    ->count('content_id');

                // Total de comentarios del usuario
                $lastCourse->total_coments = AcaThemeComment::where('course_id', $courseId)
                    ->where('user_id', Auth::id())
                    ->count();
            }
        }

        $isBirthday = Person::where('id', Auth::user()->person_id)
            ->whereMonth('birthdate', now()->month)
            ->whereDay('birthdate', now()->day)
            ->exists();

        return [
            'categoriesArticles' => $categoriesArticles,
            'popularCourses' => $popularCourses,
            'student' => $student,
            'popularArticles' => $articles,
            'lastCourse' => $lastCourse,
            'isBirthday' => $isBirthday
        ];
    }
}
