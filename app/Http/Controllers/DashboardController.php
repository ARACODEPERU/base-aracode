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
                'interests' => $this->getDataStudent()
            ]);
        } else {
            $person = Person::where('id', Auth::user()->person_id)->with('district')->first();

            return Inertia::render('Dashboard', [
                'authPerson' => $person,
                'P000009' => $this->P000009
            ]);
        }
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
            ->orderByDesc('registrations')
            ->take(8)
            ->get();

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
