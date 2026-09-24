<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\StudentTestimonyAccess;
use App\Services\JobOffersAccess;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Health\Entities\HealDoctor;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Estado del Modo Super Editor compartido con el frontend.
     *
     * Nunca debe tumbar una respuesta: si las tablas del modo todavía no
     * existen (o el usuario no es admin), se devuelve el modo inactivo.
     *
     * @return array<string, mixed>
     */
    protected function superEditorState(Request $request): array
    {
        try {
            return app(\Modules\Security\Services\SuperEditorService::class)->shareData($request);
        } catch (\Throwable $e) {
            return [
                'can_use' => false,
                'active' => false,
                'expires_at' => null,
                'ttl_minutes' => 30,
                'dirty' => 0,
                'roles' => [],
                'protected_permissions' => [],
                'role' => 'admin',
            ];
        }
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user() ? $request->user()->roles->pluck('name') : [],
                'permissions' => $request->user() ? $request->user()->getPermissionsViaRoles()->pluck('name') : [],
            ],
            'hasActiveSubscription' => fn () => JobOffersAccess::hasActiveSubscription(
                JobOffersAccess::studentId($request->user())
            ),
            // Acceso a la vista "Ofertas Laborales": curso de pago o suscripcion activa y vigente
            'canViewJobOffers' => fn () => JobOffersAccess::canView($request->user()),
            // Acceso al apartado "Testimonios" del alumno (misma regla de acceso)
             'canLeaveTestimonials' => fn () => StudentTestimonyAccess::canParticipate($request->user()),
                        // Acceso al apartado "Testimonios" del alumno (misma regla de acceso)
                        'canLeaveTestimonials' => fn () => StudentTestimonyAccess::canParticipate($request->user()),
             'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'flash' => [
                'message' => fn () => $request->session()->get('message')
            ],
            // Estado del Modo Super Editor (activo, borrador pendiente, vencimiento).
            // El frontend no guarda este estado: lo recibe en cada respuesta, así
            // que sobrevive a los reloads que vuelven a montar las directivas.
            'superEditor' => fn () => $this->superEditorState($request),
            'csrf_token' => fn () => csrf_token(),
            'health' => function () use ($request) {
                $user = $request->user();

                if (!$user) {
                    return ['currentDoctor' => null];
                }

                $doctor = HealDoctor::with('person')
                    ->where('user_id', $user->id)
                    ->when($user->person_id, function ($query) use ($user) {
                        $query->orWhere('person_id', $user->person_id);
                    })
                    ->first();

                if (!$doctor) {
                    return ['currentDoctor' => null];
                }

                return [
                    'currentDoctor' => [
                        'code' => $doctor->id,
                        'name' => $doctor->person?->full_name,
                        'colegiatura' => $doctor->colegiatura,
                        'profession' => $doctor->profession,
                        'specialty' => $doctor->specialty,
                        'service_type' => $doctor->attention_service_type ?: 'general',
                        'has_custom_pin' => (bool) $doctor->signature_pin_hash,
                    ],
                ];
            },
        ]);
    }
}
