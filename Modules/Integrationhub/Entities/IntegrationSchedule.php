<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationSchedule extends Model
{
    use HasFactory;

    protected $table = 'integration_schedules';

    protected $fillable = [
        'integration_id',
        'target_type',
        'endpoint_id',
        'api_route_name',
        'cron_expression',
        'payload',
        'is_active',
        'last_executed_at',
        'next_execution_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
        'next_execution_at' => 'datetime'
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
