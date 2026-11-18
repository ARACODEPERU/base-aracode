<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de una llamada del ERP al SIRE: método, endpoint, código HTTP,
 * duración y mensaje de SUNAT. Nunca guarda tokens ni credenciales.
 */
class SireSubmissionLog extends Model
{
    protected $fillable = [
        'sire_submission_id',
        'company_id',
        'action',
        'method',
        'endpoint',
        'http_status',
        'success',
        'message',
        'duration_ms',
        'payload',
    ];

    protected $casts = [
        'success' => 'boolean',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'payload' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SireSubmission::class, 'sire_submission_id');
    }
}
