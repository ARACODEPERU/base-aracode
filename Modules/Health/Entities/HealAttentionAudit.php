<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealAttentionAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'attention_id',
        'actor_user_id',
        'actor_doctor_id',
        'affected_doctor_id',
        'event',
        'before_data',
        'after_data',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
    ];
}
