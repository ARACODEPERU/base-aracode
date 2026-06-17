<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
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
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'flash' => [
                'message' => fn () => $request->session()->get('message')
            ],
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
