<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de una llamada a una herramienta MCP del Académico.
 */
class McpAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'tool',
        'status',
        'arguments',
        'error',
        'duration_ms',
        'ip',
    ];

    protected $casts = [
        'arguments' => 'array',
        'duration_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
