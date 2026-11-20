<?php

namespace Modules\Ai\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Tarifa editable de un modelo, en dólares por millón de tokens.
 */
class AiModelPrice extends Model
{
    protected $table = 'ai_model_prices';

    protected $fillable = [
        'provider',
        'model',
        'input_price_per_million',
        'output_price_per_million',
        'currency',
        'active',
    ];

    protected $casts = [
        'input_price_per_million' => 'float',
        'output_price_per_million' => 'float',
        'active' => 'boolean',
    ];
}
