<?php

namespace Modules\Sales\Entities;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un proceso del SIRE: la exportación de la propuesta (y, en la fase siguiente,
 * el reemplazo). Guarda el ticket de SUNAT, el archivo descargado con su hash y
 * la respuesta cruda para no perder el rastro entre reintentos.
 */
class SireSubmission extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'tipo',
        'period',
        'action',
        'ticket',
        'status',
        'file_name',
        'file_path',
        'file_hash',
        'lines',
        'attempts',
        'response',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'response' => 'array',
        'lines' => 'integer',
        'attempts' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SireSubmissionLog::class, 'sire_submission_id');
    }
}
