<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationExecLog extends Model
{
    use HasFactory;

    protected $table = 'integration_exec_logs';

    protected $fillable = [
        'integration_id',
        'endpoint_id',
        'executed_at',
        'status',
        'request_payload',
        'response_body',
        'response_status_code',
        'error_message',
        'execution_time_ms'
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'request_payload' => 'array',
        'response_body' => 'array'
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'integration_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(IntegrationEndpoint::class, 'endpoint_id');
    }
}