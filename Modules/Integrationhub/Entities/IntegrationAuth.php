<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationAuth extends Model
{
    use HasFactory;

    protected $table = 'integration_auth';

    protected $fillable = [
        'integration_id',
        'field_name',
        'field_value',
        'field_type',
        'auth_location',
        'is_enabled',
        'sort_order'
    ];

    protected $casts = [
        'is_enabled' => 'boolean'
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'integration_id');
    }
}