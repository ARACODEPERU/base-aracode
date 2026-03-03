<?php

namespace Modules\Academic\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaAttendanceLink;
use Modules\Academic\Entities\AcaStudentAttendance;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Entities\AcaContent;
use App\Models\Person;
use App\Models\ExcelExportJob;
use Modules\Academic\Jobs\ExportAttendanceExcel;
use Carbon\Carbon;

class AcaAttendanceController extends Controller
{
    use ValidatesRequests;

    public function storeLink(Request $request)
    {
        $this->validate($request, [
            'content_id' => 'nullable|exists:aca_contents,id',
            'course_id' => 'nullable|exists:aca_courses,id',
            'link_code' => 'required|integer|unique:aca_attendance_links,link_code',
            'verification_code' => 'nullable|string|max:50',
            'validity_minutes' => 'required|integer|in:30,60,90,120,180,240,300'
        ]);

        $validFrom = Carbon::now();
        $validUntil = Carbon::now()->addMinutes($request->validity_minutes);

        $link = AcaAttendanceLink::create([
            'content_id' => $request->content_id,
            'course_id' => $request->course_id,
            'link_code' => $request->link_code,
            'verification_code' => $request->verification_code,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'created_by' => Auth::id()
        ]);

        return response()->json(['success' => true]);
    }

    public function registerAttendance(Request $request)
    {
        $linkCode = $request->query('code');

        $link = AcaAttendanceLink::with(['course', 'content'])->where('link_code', $linkCode)->first();

        if (!$link) {
            return Inertia::render('Academic::Attendance/RegisterAttendance', [
                'error' => 'invalid_link',
                'message' => 'El enlace no es válido. Verifica que el enlace sea correcto.',
                'link' => null,
                'course' => null,
                'content' => null,
                'requiresVerification' => false,
                'primaryColor' => 'red'
            ]);
        }

        if ($link->valid_until->isPast()) {
            return Inertia::render('Academic::Attendance/RegisterAttendance', [
                'error' => 'expired',
                'message' => 'El enlace ha expirado. Solicita un nuevo enlace a tu docente.',
                'link' => null,
                'course' => null,
                'content' => null,
                'requiresVerification' => false,
                'primaryColor' => 'red'
            ]);
        }

        return Inertia::render('Academic::Attendance/RegisterAttendance', [
            'error' => null,
            'message' => null,
            'link' => $link,
            'course' => $link->course,
            'content' => $link->content,
            'requiresVerification' => !empty($link->verification_code),
            'primaryColor' => 'red'
        ]);
    }

    public function storeAttendance(Request $request)
    {
        $this->validate($request, [
            'link_code' => 'required',
            'dni' => 'required|string|size:8',
            'verification_code' => 'nullable|string|max:50'
        ]);

        $link = AcaAttendanceLink::with(['course', 'content'])
            ->where('link_code', $request->link_code)
            ->first();

        if (!$link) {
            return back()->withErrors(['dni' => 'El enlace de asistencia no es válido.']);
        }

        if ($link->valid_until->isPast()) {
            return back()->withErrors(['dni' => 'El enlace ha expirado. Solicita un nuevo enlace a tu docente.']);
        }

        if ($link->verification_code && $link->verification_code !== $request->verification_code) {
            return back()->withErrors(['verification_code' => 'El código de verificación es incorrecto.']);
        }

        $person = Person::where('number', $request->dni)->first();

        if (!$person) {
            return back()->withErrors(['dni' => 'No se encontró una persona con el DNI ingresado.']);
        }

        $student = AcaStudent::where('person_id', $person->id)->first();

        if (!$student) {
            return back()->withErrors(['dni' => 'No estás registrado como estudiante.']);
        }

        if ($link->course_id) {
            $registration = AcaCapRegistration::where('student_id', $student->id)
                ->where('course_id', $link->course_id)
                ->first();

            if (!$registration) {
                return back()->withErrors(['dni' => 'No estás matriculado en este curso.']);
            }
        }

        $existingAttendance = AcaStudentAttendance::where('content_id', $link->content_id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingAttendance) {
            return back()->withErrors(['dni' => 'Ya registraste tu asistencia para esta clase.']);
        }

        $deviceInfo = $this->parseUserAgent($request->userAgent());

        AcaStudentAttendance::create([
            'attendance_link_id' => $link->id,
            'student_id' => $student->id,
            'course_id' => $link->course_id,
            'content_id' => $link->content_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'registered_at' => Carbon::now()
        ]);

        return redirect()->route('aca_attendance_success', [
            'course' => $link->course?->description,
            'content' => $link->content?->description
        ]);
    }

    public function success(Request $request)
    {
        return Inertia::render('Academic::Attendance/AttendanceSuccess', [
            'course' => $request->query('course'),
            'content' => $request->query('content'),
            'registeredAt' => Carbon::now()->format('d/m/Y H:i:s'),
            'primaryColor' => 'red'
        ]);
    }

    private function parseUserAgent($userAgent)
    {
        $deviceType = 'desktop';
        $browser = 'Unknown';
        $platform = 'Unknown';

        if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            $deviceType = preg_match('/tablet|ipad/i', $userAgent) ? 'tablet' : 'mobile';
        }

        if (preg_match('/windows|win32|win64/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
        }

        if (preg_match('/chrome|crios/i', $userAgent) && !preg_match('/edge|edg/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome|crios/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edge|edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform
        ];
    }

    public function administrationPanel()
    {
        $courses = AcaCourse::select('id', 'description')
            ->where('status', true)
            ->orderBy('description')
            ->get();

        return Inertia::render('Academic::Attendance/AdministrationPanel', [
            'courses' => $courses
        ]);
    }

    public function getModulesByCourse($courseId)
    {
        $modules = AcaModule::select('id', 'description')
            ->where('course_id', $courseId)
            ->orderBy('position')
            ->get();

        return response()->json($modules);
    }

    public function getThemesByModule($moduleId)
    {
        $themes = AcaTheme::select('id', 'description')
            ->where('module_id', $moduleId)
            ->orderBy('position')
            ->get();

        return response()->json($themes);
    }

    public function getContentsByTheme($themeId)
    {
        $contents = AcaContent::select('id', 'description', 'is_file')
            ->where('theme_id', $themeId)
            ->where('is_file', 3)
            ->orderBy('position')
            ->get();

        return response()->json($contents);
    }

    public function getStudentsAttendance(Request $request)
    {
        $request->validate([
            'content_id' => 'required|exists:aca_contents,id'
        ]);

        $content = AcaContent::with('theme.module.course')->find($request->content_id);
        $courseId = $content->theme->module->course_id;

        $registrations = AcaCapRegistration::with(['student.person'])
            ->where('course_id', $courseId)
            ->get();

        $attendances = AcaStudentAttendance::with('userEdit')
            ->where('content_id', $request->content_id)
            ->get()
            ->keyBy('student_id');

        $students = $registrations->map(function ($reg) use ($attendances) {
            $attendance = $attendances->get($reg->student_id);

            return [
                'student_id' => $reg->student_id,
                'name' => $reg->student->person->formatted_name ?? $reg->student->person->full_name,
                'dni' => $reg->student->person->number,
                'is_present' => $attendance !== null,
                'registered_at' => $attendance?->registered_at?->format('d/m/Y H:i:s'),
                'observation' => $attendance?->observation,
                'user_edit_id' => $attendance?->user_edit_id,
                'user_edit_name' => $attendance?->userEdit?->name,
            ];
        })->values();

        return response()->json([
            'students' => $students,
            'content' => [
                'id' => $content->id,
                'description' => $content->description
            ],
            'course' => [
                'id' => $courseId,
                'description' => $content->theme->module->course->description
            ]
        ]);
    }

    public function updateAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:aca_students,id',
            'content_id' => 'required|exists:aca_contents,id',
            'course_id' => 'required|exists:aca_courses,id',
            'action' => 'required|in:add,remove',
            'observation' => 'nullable|string|max:500'
        ]);

        if ($request->action === 'add') {
            $existing = AcaStudentAttendance::where('content_id', $request->content_id)
                ->where('student_id', $request->student_id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'El estudiante ya tiene asistencia registrada.'
                ], 400);
            }

            AcaStudentAttendance::create([
                'student_id' => $request->student_id,
                'content_id' => $request->content_id,
                'course_id' => $request->course_id,
                'attendance_link_id' => null,
                'user_edit_id' => Auth::id(),
                'observation' => $request->observation,
                'registered_at' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Asistencia registrada correctamente.'
            ]);
        }

        if ($request->action === 'remove') {
            $attendance = AcaStudentAttendance::where('content_id', $request->content_id)
                ->where('student_id', $request->student_id)
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'No existe registro de asistencia para este estudiante.'
                ], 400);
            }

            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asistencia eliminada correctamente.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Acción no válida.'
        ], 400);
    }

    public function updateObservation(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:aca_students,id',
            'content_id' => 'required|exists:aca_contents,id',
            'observation' => 'nullable|string|max:500'
        ]);

        $attendance = AcaStudentAttendance::where('content_id', $request->content_id)
            ->where('student_id', $request->student_id)
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'No existe registro de asistencia para este estudiante.'
            ], 400);
        }

        $attendance->update([
            'observation' => $request->observation,
            'user_edit_id' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Observación actualizada correctamente.'
        ]);
    }

    public function exportAttendanceExcel(Request $request)
    {
        $request->validate([
            'content_id' => 'required|exists:aca_contents,id'
        ]);

        $content = AcaContent::with('theme.module.course')->find($request->content_id);
        $courseId = $content->theme->module->course_id;

        $excelExportJob = ExcelExportJob::create([
            'user_id' => Auth::id(),
            'report_type' => 'ATTENDANCE',
            'status' => 'pending',
            'filters' => [
                'content_id' => $request->content_id,
                'course_id' => $courseId
            ]
        ]);

        ExportAttendanceExcel::dispatch(Auth::id(), $excelExportJob->id);

        return response()->json([
            'message' => 'La exportación de Excel ha sido iniciada. Por favor, espere un momento.',
            'job_id' => $excelExportJob->id
        ], 202);
    }

    public function exportAttendanceStatus($jobId)
    {
        $excelExportJob = ExcelExportJob::where('id', $jobId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$excelExportJob) {
            return response()->json([
                'message' => 'Estado de exportación no encontrado o no autorizado.'
            ], 404);
        }

        return response()->json($excelExportJob);
    }
}
