<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\StudentTestimonyAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\CMS\Entities\CmsTestimony;
use Modules\Onlineshop\Entities\OnliItem;

class AcaStudentTestimonyController extends Controller
{
    /**
     * Apartado "Testimonios" del alumno: cursos culminados y testimonios enviados.
     */
    public function index()
    {
        $user = Auth::user();

        if (!StudentTestimonyAccess::canParticipate($user)) {
            return redirect()->route('web_subscriptions')
                ->with('message', 'El apartado de Testimonios está disponible con un curso de pago o una suscripción activa.');
        }

        $studentId = StudentTestimonyAccess::studentId($user);

        $myTestimonies = CmsTestimony::with('course.category')
            ->where('source', CmsTestimony::SOURCE_STUDENT)
            ->where('student_id', $studentId)
            ->where('approval_status', '!=', CmsTestimony::STATUS_REJECTED)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (CmsTestimony $testimony) => StudentTestimonyAccess::testimonyPayload($testimony));

        return Inertia::render('Academic::Students/Testimonials', [
            'courses' => StudentTestimonyAccess::completedCourses($user),
            'myTestimonies' => $myTestimonies,
            'consentText' => StudentTestimonyAccess::consentText(),
            'editWindowHours' => CmsTestimony::EDIT_WINDOW_HOURS,
        ]);
    }

    /**
     * Registra el testimonio del alumno sobre un curso culminado.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $studentId = StudentTestimonyAccess::studentId($user);

        if (!$studentId || !StudentTestimonyAccess::canParticipate($user)) {
            return redirect()->route('web_subscriptions')
                ->with('message', 'El apartado de Testimonios está disponible con un curso de pago o una suscripción activa.');
        }

        $validated = $this->validateTestimony($request);

        $course = StudentTestimonyAccess::completedCourses($user)
            ->firstWhere('id', (int) $validated['course_id']);

        if (!$course) {
            throw ValidationException::withMessages([
                'course_id' => 'Solo puedes dejar tu testimonio de cursos que hayas culminado al 100%.',
            ]);
        }

        $existing = CmsTestimony::where('source', CmsTestimony::SOURCE_STUDENT)
            ->where('student_id', $studentId)
            ->where('course_id', $course['id'])
            ->first();

        if ($existing && $existing->approval_status === CmsTestimony::STATUS_APPROVED) {
            return back()->with('message', 'Tu testimonio de este curso ya fue publicado.');
        }

        if ($existing && $existing->approval_status === CmsTestimony::STATUS_PENDING) {
            return back()->with('message', 'Ya registraste un testimonio para este curso y está en revisión.');
        }

        $payload = $this->payload($request, $user, $studentId, $course, $existing);

        CmsTestimony::updateOrCreate(
            [
                'source' => CmsTestimony::SOURCE_STUDENT,
                'student_id' => $studentId,
                'course_id' => $course['id'],
            ],
            $payload
        );

        return back()->with('message', '¡Gracias! Tu testimonio fue enviado y está en revisión.');
    }

    /**
     * Edita el testimonio dentro de la ventana de 15 horas.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $user = Auth::user();
        $studentId = StudentTestimonyAccess::studentId($user);

        $testimony = CmsTestimony::where('source', CmsTestimony::SOURCE_STUDENT)->findOrFail($id);

        if (!StudentTestimonyAccess::canEdit($testimony, $studentId)) {
            $message = $testimony->approval_status === CmsTestimony::STATUS_APPROVED
                ? 'Tu testimonio ya fue publicado, por eso no puede modificarse.'
                : 'Ya no puedes modificar este testimonio.';

            return back()->with('message', $message);
        }

        $validated = $this->validateTestimony($request);

        $course = StudentTestimonyAccess::completedCourses($user)
            ->firstWhere('id', (int) $validated['course_id']);

        if (!$course || (int) $course['id'] !== (int) $testimony->course_id) {
            throw ValidationException::withMessages([
                'course_id' => 'El testimonio solo puede editarse para el mismo curso culminado.',
            ]);
        }

        $payload = $this->payload($request, $user, $studentId, $course, $testimony, true);

        // Al editarlo vuelve a revision y se renueva la constancia de autorizacion.
        $payload['approval_status'] = CmsTestimony::STATUS_PENDING;
        $payload['status'] = true;

        $testimony->update($payload);

        return back()->with('message', 'Tu testimonio fue actualizado y está en revisión.');
    }

    /**
     * Elimina el testimonio dentro de la ventana de 15 horas.
     */
    public function destroy($id): RedirectResponse
    {
        $user = Auth::user();
        $studentId = StudentTestimonyAccess::studentId($user);

        $testimony = CmsTestimony::where('source', CmsTestimony::SOURCE_STUDENT)->findOrFail($id);

        if (!StudentTestimonyAccess::canEdit($testimony, $studentId)) {
            $message = $testimony->approval_status === CmsTestimony::STATUS_APPROVED
                ? 'Tu testimonio ya fue publicado, por eso no puede eliminarse.'
                : 'Ya no puedes eliminar este testimonio.';

            return back()->with('message', $message);
        }

        $testimony->delete();

        return back()->with('message', 'Tu testimonio fue eliminado.');
    }

    /**
     * Validacion compartida por store() y update().
     */
    private function validateTestimony(Request $request): array
    {
        return $request->validate([
            'course_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:20|max:2000',
            'author_role' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'consent' => 'accepted',
        ], [
            'course_id.required' => 'Debes elegir el curso del que hablarás.',
            'rating.required' => 'Selecciona tu calificación en estrellas.',
            'rating.min' => 'La calificación mínima es 1 estrella.',
            'rating.max' => 'La calificación máxima es 5 estrellas.',
            'comment.required' => 'El comentario es obligatorio.',
            'comment.min' => 'Cuéntanos un poco más: el comentario debe tener al menos 20 caracteres.',
            'comment.max' => 'El comentario no debe superar los 2000 caracteres.',
            'author_role.max' => 'El cargo o profesión solo acepta 255 caracteres.',
            'photo.image' => 'La imagen que compartiste no es válida.',
            'photo.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'photo.max' => 'La imagen no debe pesar más de 2 MB.',
            'consent.accepted' => 'Debes autorizar la publicación de tu testimonio para poder guardarlo.',
        ]);
    }

    /**
     * Datos que se persisten para el testimonio del alumno.
     */
    private function payload(Request $request, $user, int $studentId, array $course, ?CmsTestimony $existing = null, bool $isUpdate = false): array
    {
        $path = $existing?->image;

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('uploads/testimonies', 'public');
        }

        $courseModel = AcaCourse::find($course['id']);

        $payload = [
            'item_id' => $courseModel
                ? OnliItem::where('entitie', 'Modules-Academic-Entities-AcaCourse')
                    ->where('item_id', $courseModel->id)
                    ->value('id')
                : null,
            'entitie' => OnliItem::class,
            'student_id' => $studentId,
            'course_id' => $course['id'],
            'title' => $course['description'],
            'description' => $request->input('comment'),
            'rating' => (int) $request->input('rating'),
            'source' => CmsTestimony::SOURCE_STUDENT,
            'author_name' => $user->person?->full_name ?: $user->name,
            'author_role' => $request->input('author_role') ?: null,
            'image' => $path,
            'consent_accepted' => true,
            'consent_accepted_at' => now(),
            'consent_version' => StudentTestimonyAccess::consentVersion(),
            'video' => null,
            'status' => true,
        ];

        if (!$isUpdate) {
            $payload['approval_status'] = CmsTestimony::STATUS_PENDING;
            $payload['approved_at'] = null;
            $payload['approved_by'] = null;

            // Si venia de un rechazo, se reinicia la ventana de edicion de 15 horas.
            if ($existing && $existing->approval_status === CmsTestimony::STATUS_REJECTED) {
                $payload['created_at'] = now();
            }
        }

        return $payload;
    }
}
