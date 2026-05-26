<?php

namespace Modules\Health\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Health\Entities\HealActivity;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealPatient;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            $model->logActivity('created', ['attributes' => $model->getAttributes()]);
        });

        static::updated(function (Model $model) {
            $changes = [
                'before' => collect($model->getOriginal())->only(array_keys($model->getChanges()))->all(),
                'after' => $model->getChanges(),
                'changed_fields' => array_keys($model->getChanges()),
            ];
            $model->logActivity('updated', $changes);
        });

        static::deleted(function (Model $model) {
            $model->logActivity('deleted', [
                'deleted_attributes' => $model->getAttributes(),
            ]);
        });

        if (method_exists(static::class, 'bootSoftDeletes')) {
            static::restored(function (Model $model) {
                $model->logActivity('restored', ['attributes' => $model->getAttributes()]);
            });
        }
    }

    protected function logActivity(string $activityType, ?array $metadata = null): void
    {
        $user = Auth::user();
        $doctor = null;

        if ($user) {
            $doctor = HealDoctor::where('user_id', $user->id)
                ->orWhere('person_id', $user->person_id)
                ->first();
        }

        $doctorId = $doctor?->id ?? ($this->doctor_id ?? null);
        $patientId = $this->resolvePatientId();
        $patientDisplay = null;

        if ($patientId) {
            $patient = HealPatient::with('person')->find($patientId);
            $patientDisplay = $patient?->person?->full_name;
        }

        HealActivity::create([
            'actor_type' => $user ? get_class($user) : null,
            'actor_id' => $user?->id,
            'actor_person_id' => $user?->person_id ?? $doctor?->person_id,
            'actor_display' => $doctor?->person?->full_name ?? $user?->name ?? 'Sistema',
            'activity_type' => $activityType,
            'subject_type' => get_class($this),
            'subject_id' => $this->id ?? $this->getKey(),
            'patient_id' => $patientId,
            'patient_display' => $patientDisplay,
            'metadata' => array_merge($metadata ?? [], [
                'subject_name' => $this->resolveSubjectName(),
            ]),
            'ip' => request()->header('X-Forwarded-For')
                ? trim(explode(',', request()->header('X-Forwarded-For'))[0])
                : (request()->header('X-Real-IP') ?? request()->ip()),
            'user_agent' => substr((string) request()->userAgent(), 0, 1000),
        ]);
    }

    protected function resolvePatientId(): ?int
    {
        if (isset($this->patient_id)) {
            return $this->patient_id;
        }

        // Si el modelo es el paciente mismo, usamos su propio ID
        if ($this instanceof \Modules\Health\Entities\HealPatient) {
            return $this->id;
        }

        return null;
    }

    protected function resolveSubjectName(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        if (isset($this->full_name)) {
            return $this->full_name;
        }

        $relations = ['person', 'patient.person', 'creator'];

        foreach ($relations as $relation) {
            if (method_exists($this, $relation) && $this->$relation) {
                $related = $this->$relation;

                if (isset($related->full_name)) {
                    return $related->full_name;
                }
            }
        }

        return class_basename($this) . ' #' . ($this->id ?? $this->getKey());
    }
}
