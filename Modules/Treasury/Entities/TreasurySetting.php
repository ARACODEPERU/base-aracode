<?php

namespace Modules\Treasury\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración única del módulo de Tesorería: si está activo y desde qué
 * fecha cuenta. Los cambios se registran en la auditoría del módulo.
 */
class TreasurySetting extends Model
{
    protected $table = 'treasury_settings';

    protected $fillable = [
        'enabled',
        'starts_on',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'starts_on' => 'date',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
