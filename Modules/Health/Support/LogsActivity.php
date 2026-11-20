<?php

namespace Modules\Health\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\Health\Entities\HealActivity;
use Modules\Health\Entities\HealPatient;

/**
 * Auditoría automática de los modelos de Salud.
 *
 * Cada evento CRUD escribe en heal_activities a través de HealthAudit, que
 * añade actor, paciente, IP, petición, huellas del antes/después y la cadena
 * de hashes. Los campos sensibles (contraseñas, PIN, tokens) se filtran.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            $model->logActivity('created', [
                'attributes' => $model->getAttributes(),
            ], null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $changed = array_keys($model->getChanges());

            $model->logActivity('updated', [
                'changed_fields' => $changed,
            ], $model->getOriginal(), $model->getAttributes());
        });

        static::deleted(function (Model $model) {
            $model->logActivity('deleted', [
                'deleted_attributes' => $model->getAttributes(),
            ], $model->getAttributes(), null);
        });

        if (method_exists(static::class, 'bootSoftDeletes')) {
            static::restored(function (Model $model) {
                $model->logActivity('restored', [
                    'attributes' => $model->getAttributes(),
                ], null, $model->getAttributes());
            });
        }
    }

    /**
     * Registra una actividad que no corresponde a un evento CRUD del modelo
     * (por ejemplo, la anonimización de los datos de un paciente).
     */
    public function registrarActividad(string $activityType, ?array $metadata = null, string $category = HealthAudit::CATEGORY_CLINICAL): HealActivity
    {
        return $this->logActivity($activityType, $metadata, null, null, $category);
    }

    protected function logActivity(
        string $activityType,
        ?array $metadata = null,
        ?array $before = null,
        ?array $after = null,
        ?string $category = null,
    ): HealActivity {
        return HealthAudit::record($activityType, [
            'subject' => $this,
            'patient_id' => $this->resolvePatientId(),
            'metadata' => array_merge($metadata ?? [], [
                'subject_name' => $this->resolveSubjectName(),
            ]),
            'before' => $before,
            'after' => $after,
            'category' => $category ?? $this->auditCategory(),
        ]);
    }

    /**
     * Categoría por defecto del modelo; los modelos pueden sobrescribirla.
     */
    protected function auditCategory(): string
    {
        return HealthAudit::CATEGORY_CLINICAL;
    }

    protected function resolvePatientId(): ?int
    {
        if (isset($this->patient_id)) {
            return (int) $this->patient_id;
        }

        // Si el modelo es el paciente mismo, usamos su propio ID.
        if ($this instanceof HealPatient) {
            return (int) $this->getKey();
        }

        return null;
    }

    protected function resolveSubjectName(): string
    {
        if (isset($this->name)) {
            return (string) $this->name;
        }

        if (isset($this->full_name)) {
            return (string) $this->full_name;
        }

        foreach (['person', 'patient.person', 'creator'] as $relation) {
            if (method_exists($this, $relation) && $this->$relation) {
                $related = $this->$relation;

                if (isset($related->full_name)) {
                    return (string) $related->full_name;
                }
            }
        }

        return class_basename($this) . ' #' . ($this->getKey() ?? 'nuevo');
    }
}
