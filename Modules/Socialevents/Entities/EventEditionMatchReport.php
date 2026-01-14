<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class EventEditionMatchReport extends Model
{
    use HasFactory;

    protected $table = 'event_edition_match_reports';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'match_id',
        'local_score',
        'visitor_score',
        'referees',
        'observations',
        'closed_at',
        'closed_by',
        'has_protest',
        'protest_details',
        'protest_status',
        'resolution_details',
        'points_processed',
        'minutes_file_name',
        'minutes_file_path',
        'payment_arvitraje_h',
        'payment_arvitraje_a',
        'status'
    ];

    protected $casts = [
        'protest_details' => 'array',
        'referees'        => 'array',
    ];

    protected function protestDetails(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true) ?: [],
            set: fn ($value) => json_encode($value),
        );
    }

    /**
     * Manejo automático para referees
     */
    protected function referees(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true) ?: [],
            set: fn ($value) => json_encode($value),
        );
    }

    protected static function boot()
    {
        parent::boot();

        // Se ejecuta justo antes de insertar el registro en la base de datos
        static::creating(function ($model) {
            $year = date('Y'); // Obtiene el año actual (2026 en tu caso)
            $prefix = "AP" . $year . "-";

            // Buscamos el último registro que tenga el prefijo de este año
            $lastRecord = self::where('minutes_code', 'LIKE', $prefix . '%')
                ->orderBy(DB::raw('CAST(SUBSTRING(minutes_code, 8) AS UNSIGNED)'), 'desc')
                ->first();

            if ($lastRecord) {
                // Extraemos el número después del guion y le sumamos 1
                $lastNumber = (int) substr($lastRecord->minutes_code, strlen($prefix));
                $newNumber = $lastNumber + 1;
            } else {
                // Si es el primero del año, empezamos en 1
                $newNumber = 1;
            }

            // Asignamos el código generado al modelo
            $model->minutes_code = $prefix . $newNumber;
        });
    }

    public function partido(): HasOne
    {
        return $this->hasOne(EventEditionMatch::class, 'id', 'match_id');
    }

    /**
     * MUTATOR: Se ejecuta automáticamente al GUARDAR en la DB
     * Evita inyecciones de código HTML malicioso.
     */
    public function setResolutionDetailsAttribute($value)
    {
        $this->attributes['resolution_details'] = htmlspecialchars(
            strip_tags($value),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * ACCESSOR: Se ejecuta automáticamente al LEER de la DB
     * Opcional: Si necesitas decodificar entidades para mostrar en el PDF
     */
    public function getResolutionDetailsAttribute($value)
    {
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
}
