<?php

namespace Modules\Health\Support;

use Illuminate\Support\Facades\Auth;
use Modules\Health\Entities\HealAttention;
use Modules\Health\Entities\HealDoctor;

class PendingSignatureReminder
{
    public static function items(): array
    {
        return HealAttention::with(['patient.person', 'doctor.person'])
            ->whereNull('signed_at')
            ->when(!self::canSeeAllDoctors(), function ($query) {
                $doctor = self::currentDoctor();

                if ($doctor) {
                    $query->where('doctor_id', $doctor->id);
                    return;
                }

                $query->whereRaw('1 = 0');
            })
            ->latest('attention_at')
            ->limit(12)
            ->get()
            ->map(fn (HealAttention $attention) => [
                'id' => $attention->id,
                'attention_at' => $attention->attention_at,
                'patient_name' => $attention->patient?->person?->full_name,
                'doctor_name' => $attention->doctor?->person?->full_name,
                'service_type' => $attention->service_type,
            ])
            ->values()
            ->all();
    }

    private static function canSeeAllDoctors(): bool
    {
        $user = Auth::user();

        return !$user || $user->hasAnyRole(['admin', 'Admin', 'Administrador', 'webAdmin']);
    }

    private static function currentDoctor(): ?HealDoctor
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return HealDoctor::query()
            ->where('user_id', $user->id)
            ->when($user->person_id, function ($query) use ($user) {
                $query->orWhere('person_id', $user->person_id);
            })
            ->first();
    }
}
