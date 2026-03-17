<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'url',
        'request_payload',
        'ip',
        'user_agent',
        'status_code',
        'error_message',
        'details_data',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'details_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
