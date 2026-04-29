<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationQuery extends Model
{
    use HasFactory;

    protected $table = 'integration_queries';

    protected $fillable = [
        'integration_id',
        'query_name',
        'query_sql',
        'query_type',
        'parameters',
        'is_active'
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean'
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'integration_id');
    }
}