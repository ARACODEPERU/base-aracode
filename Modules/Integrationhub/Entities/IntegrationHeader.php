<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationHeader extends Model
{
    use HasFactory;

    protected $table = 'integration_headers';

    protected $fillable = [
        'integration_id',
        'header_name',
        'header_value',
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