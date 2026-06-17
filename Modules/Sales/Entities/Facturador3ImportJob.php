<?php

namespace Modules\Sales\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facturador3ImportJob extends Model
{
    protected $fillable = [
        'user_id',
        'phase',
        'status',
        'progress',
        'processed_count',
        'total_count',
        'chunk_size',
        'last_processed_id',
        'meta',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
