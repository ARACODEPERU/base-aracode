<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Database\factories\EventEditionAccordanceFactory;

class EventEditionAccordance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'edition_id',
        'minutes_subject',
        'minutes_type',
        'minutes_code',
        'meeting_date',
        'participants',
        'minutes_body',
        'user_by',
        'status',
        'observations'
    ];

    protected $casts = [
        'participants' => 'array',
        'meeting_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Se ejecuta justo antes de insertar el registro en la base de datos
        static::creating(function ($model) {
            $year = date('Y'); // Obtiene el año actual (2026 en tu caso)
            $prefix = "AC" . $year . "-";

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

    public function setMinutesBodyAttribute($value)
    {
        $this->attributes['minutes_body'] = htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }

    public function getMinutesBodyAttribute($value)
    {
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }

    public function edicion(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id', 'id');
    }
}
