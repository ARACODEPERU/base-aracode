<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationFieldMap extends Model
{
    use HasFactory;

    protected $table = 'integration_field_maps';

    protected $fillable = [
        'endpoint_id',
        'field_key',
        'field_value',
        'field_type',
        'source_type',
        'source_table',
        'source_field',
        'default_value',
        'is_enabled',
        'transform_rule',
        'sort_order',
        'has_subitems',
        'subitems',
        'structure_type',
        'array_query',
        'default_type',
        'default_table',
        'default_field'
    ];

    protected $casts = [
        'transform_rule' => 'array',
        'is_enabled' => 'boolean',
        'has_subitems' => 'boolean',
        'subitems' => 'array'
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(IntegrationEndpoint::class, 'endpoint_id');
    }
}