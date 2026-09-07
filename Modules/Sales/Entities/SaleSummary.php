<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleSummary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'summary_name',
        'generation_date',
        'summary_date',
        'correlative',
        'sunat_points',
        'ticket',
        'cdr',
        'xml',
        'pdf',
        'response_code',
        'response_description',
        'notes',
        'status',
        'user_id',
        'anio',
        'reason'
    ];


    // Definimos el evento "creating"
    protected static function boot()
    {
        parent::boot();

        static::created(function ($summary) {
            // El correlativo es monótono creciente: se toma el máximo histórico
            // (incluidos los registros eliminados con soft delete) para no reutilizar
            // un correlativo que SUNAT ya recibió.
            $lastCorrelativo = static::withTrashed()
                ->where('id', '<>', $summary->id)
                ->max('correlative');

            $correlativo = str_pad((int) $lastCorrelativo + 1, 5, '0', STR_PAD_LEFT);

            $summary->update(['correlative' => $correlativo]);
        });
    }

    public function details(): HasMany
    {
        return $this->hasMany(SaleSummaryDetail::class,'summary_id');
    }
}
