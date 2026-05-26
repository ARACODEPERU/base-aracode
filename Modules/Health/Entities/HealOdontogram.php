<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealOdontogram extends Model
{
    use HasFactory;

    protected $fillable = [
        'attention_id',
        'teeth',
        'notes',
    ];

    protected $casts = [
        'teeth' => 'array',
    ];

    public function attention(): BelongsTo
    {
        return $this->belongsTo(HealAttention::class, 'attention_id');
    }
}
