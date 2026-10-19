<?php

namespace Modules\Academic\Entities;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\factories\AcaStudentSubscriptionFactory;

class AcaStudentSubscription extends Model
{
    use HasFactory;
    public $incrementing = false; // Deshabilitar incremento automático
    protected $primaryKey = ['student_id', 'subscription_id'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'subscription_id',
        'date_start',
        'date_end',
        'status',
        'notes',
        'renewals',
        'registration_user_id',
        'onli_sale_id',
        'xdocument_id',
        'amount_paid',
        'xsale_note_id'
    ];

    /**
     * Suscripciones vigentes hoy.
     *
     * El estado manda, salvo que la fecha de fin ya haya pasado: así el acceso
     * no depende de que el comando de vencimiento haya corrido. Una suscripción
     * dentro de sus fechas cuenta aunque su estado aún no se haya actualizado.
     */
    public function scopeActiveNow(Builder $query): Builder
    {
        $today = Carbon::today();

        return $query->where(function (Builder $query) use ($today) {
            // Activa por estado y sin vencer. Las suscripciones sin fecha de fin
            // se rigen solo por su estado.
            $query->where(function (Builder $q) use ($today) {
                $q->where('status', true)
                    ->where(function (Builder $q2) use ($today) {
                        $q2->whereNull('date_end')
                            ->orWhereDate('date_end', '>=', $today);
                    });
            })
                // Vigente por fechas, aunque el estado no se haya actualizado.
                ->orWhere(function (Builder $q) use ($today) {
                    $q->whereNotNull('date_start')
                        ->whereNotNull('date_end')
                        ->whereDate('date_start', '<=', $today)
                        ->whereDate('date_end', '>=', $today);
                });
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AcaSubscriptionType::class, 'subscription_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaStudent::class, 'student_id', 'id');
    }
    public function salenote(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'xsale_note_id');
    }
}
