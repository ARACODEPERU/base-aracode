<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolArea;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Entities\AcaVirtualCourse;
use Modules\Academic\Entities\AcaVirtualUnit;
use Modules\Academic\Services\SchoolContextService;

class AcaVirtualClassroomController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    public function index(Request $request)
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            return Inertia::render('Academic::School/VirtualClassroom/Index', [
                'school' => null,
                'years' => [],
                'yearId' => null,
                'activeYearId' => null,
                'yearIsActive' => false,
                'courses' => [],
                'canManage' => false,
                'canCopy' => false,
            ]);
        }

        $years = AcaSchoolYear::where('school_id', $school->id)->orderByDesc('year')->get(['id', 'year', 'status']);
        $activeYear = $years->firstWhere('status', AcaSchoolYear::STATUS_ACTIVE);
        $yearId = (int) ($request->query('year_id') ?: ($activeYear->id ?? ($years->first()->id ?? 0)));

        if (! $yearId) {
            return Inertia::render('Academic::School/VirtualClassroom/Index', [
                'school' => ['id' => $school->id, 'name' => $school->name],
                'years' => $years,
                'yearId' => null,
                'activeYearId' => $activeYear?->id,
                'yearIsActive' => false,
                'courses' => [],
                'canManage' => $this->canManage(),
                'canCopy' => false,
            ]);
        }

        // Generación automática para docente logueado en año activo
        if ($activeYear && (int) $yearId === (int) $activeYear->id) {
            $this->ensureCoursesForCurrentUser($school->id, (int) $yearId);
        }

        $personId = Auth::user()?->person_id;
        $canManage = $this->canManage();

        $query = AcaVirtualCourse::with(['section.grade.level', 'area', 'teacher'])
            ->where('school_id', $school->id)
            ->where('year_id', $yearId);

        if (! $canManage && $personId) {
            $query->where('teacher_person_id', $personId);
        } elseif (! $canManage && ! $personId) {
            $query->whereRaw('1 = 0');
        }

        $courses = $query->orderBy('id')->get()->map(function (AcaVirtualCourse $course) {
            $unitsCount = $course->units()->count();

            return [
                'id' => $course->id,
                'name' => $course->name ?: trim(($course->area?->name ?? '').' - '.($course->section?->grade?->name ?? '').' '.($course->section?->name ?? '')),
                'area_name' => $course->area?->name,
                'level_name' => $course->section?->grade?->level?->name,
                'grade_name' => $course->section?->grade?->name,
                'section_name' => $course->section?->name,
                'teacher_name' => $course->teacher?->full_name,
                'units_count' => $unitsCount,
                'can_copy' => $unitsCount === 0,
            ];
        })->values();

        return Inertia::render('Academic::School/VirtualClassroom/Index', [
            'school' => ['id' => $school->id, 'name' => $school->name],
            'years' => $years,
            'yearId' => $yearId,
            'activeYearId' => $activeYear?->id,
            'yearIsActive' => $activeYear && (int) $yearId === (int) $activeYear->id,
            'courses' => $courses,
            'canManage' => $canManage,
        ]);
    }

    public function show(Request $request, int $courseId)
    {
        $school = $this->context->currentSchool();
        $course = AcaVirtualCourse::with(['section.grade.level', 'area', 'year', 'teacher', 'units.lessons'])
            ->where('school_id', $school?->id)
            ->findOrFail($courseId);

        $activeYear = AcaSchoolYear::where('school_id', $school?->id)
            ->where('status', AcaSchoolYear::STATUS_ACTIVE)
            ->first();

        $yearIsActive = $activeYear && (int) $course->year_id === (int) $activeYear->id;

        if (! $this->canAccessCourse($course)) {
            abort(403);
        }

        return Inertia::render('Academic::School/VirtualClassroom/Course', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name ?: trim(($course->area?->name ?? '').' - '.($course->section?->grade?->name ?? '').' '.($course->section?->name ?? '')),
                'area_name' => $course->area?->name,
                'section_label' => trim(($course->section?->grade?->name ?? '').' '.($course->section?->name ?? '')),
                'level_name' => $course->section?->grade?->level?->name,
                'teacher_name' => $course->teacher?->full_name,
                'year' => $course->year?->year,
            ],
            'units' => $course->units->map(fn (AcaVirtualUnit $unit) => [
                'id' => $unit->id,
                'title' => $unit->title,
                'purpose' => $unit->purpose,
                'sort_order' => $unit->sort_order,
                'lessons' => $unit->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'sort_order' => $lesson->sort_order,
                ])->values(),
            ])->values(),
            'yearIsActive' => $yearIsActive,
            'canManage' => $this->canManage() && $yearIsActive,
        ]);
    }

    private function canManage(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->can('aca_virtual_configurar') || $user->can('aca_virtual_unidad_gestionar');
    }

    private function canAccessCourse(AcaVirtualCourse $course): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->can('aca_virtual_configurar')) {
            return true;
        }

        $personId = $user->person_id;

        return $personId && (int) $course->teacher_person_id === (int) $personId;
    }

    private function ensureCoursesForCurrentUser(int $schoolId, int $yearId): void
    {
        $user = Auth::user();
        $personId = $user?->person_id;

        if (! $personId) {
            return;
        }

        // Detectar áreas/secciones asignadas al docente por horario
        $assignments = DB::table('aca_school_schedules')
            ->join('aca_school_sections', 'aca_school_sections.id', '=', 'aca_school_schedules.section_id')
            ->join('aca_school_grades', 'aca_school_grades.id', '=', 'aca_school_sections.grade_id')
            ->join('aca_school_areas', 'aca_school_areas.id', '=', 'aca_school_schedules.area_id')
            ->where('aca_school_schedules.school_id', $schoolId)
            ->where('aca_school_schedules.year_id', $yearId)
            ->where('aca_school_schedules.teacher_person_id', $personId)
            ->where('aca_school_schedules.status', true)
            ->select('aca_school_schedules.section_id', 'aca_school_schedules.area_id')
            ->distinct()
            ->get();

        foreach ($assignments as $assignment) {
            AcaVirtualCourse::firstOrCreate(
                [
                    'school_id' => $schoolId,
                    'year_id' => $yearId,
                    'section_id' => $assignment->section_id,
                    'area_id' => $assignment->area_id,
                ],
                [
                    'teacher_person_id' => $personId,
                    'status' => true,
                    'sort_order' => 0,
                ]
            );
        }
    }
}
