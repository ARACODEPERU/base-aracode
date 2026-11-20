<?php

namespace Modules\Ai\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Límite de IA propio de un rol. Un 0 no agrega límite.
 */
class AiRoleLimit extends Model
{
    protected $table = 'ai_role_limits';

    protected $fillable = [
        'role_name',
        'daily_limit',
        'monthly_budget_usd',
    ];

    protected $casts = [
        'daily_limit' => 'integer',
        'monthly_budget_usd' => 'float',
    ];
}
