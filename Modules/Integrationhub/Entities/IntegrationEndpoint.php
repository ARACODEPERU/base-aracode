<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationEndpoint extends Model
{
    use HasFactory;

    protected $table = 'integration_endpoints';

    protected $fillable = [
        'integration_id',
        'name',
        'endpoint_path',
        'http_method',
        'body_type',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'integration_id');
    }
    public function fieldmaps()
    {
        return $this->hasMany(IntegrationFieldmap::class, 'endpoint_id');
    }
}
