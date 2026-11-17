<?php

namespace Modules\Treasury\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Auditoría de dominio del módulo, solo inserción.
 *
 * No se actualiza ni se borra: el modelo rechaza ambas operaciones y la tabla
 * no tiene `updated_at`. Es el rastro que se revisa cuando algo no cuadra.
 */
class TreasuryAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entity',
        'entity_id',
        'action',
        'old_values',
        'new_values',
        'meta',
        'user_id',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('La auditoría de tesorería es inmutable.');
        });

        static::deleting(function () {
            throw new \RuntimeException('La auditoría de tesorería no se puede borrar.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
