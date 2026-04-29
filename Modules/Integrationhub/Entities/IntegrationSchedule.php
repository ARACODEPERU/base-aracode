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
        'cron_expression',
        'is_active',
        'last_executed_at',
        'next_execution_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
        'next_execution_at' => 'datetime'
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'integration_id');
    }
}