<?php

namespace Modules\Ai\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una consulta de IA registrada. Es la base de las estadísticas de consumo:
 * usuario, proveedor, modelo, tokens y costo estimado.
 */
class AiUsage extends Model
{
    protected $table = 'ai_usages';

    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'screen',
        'status',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_usd',
        'prompt_excerpt',
        'error',
    ];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost_usd' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
