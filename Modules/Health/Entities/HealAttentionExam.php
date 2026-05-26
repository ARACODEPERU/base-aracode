<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealAttentionExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'attention_id',
        'exam_type',
        'name',
        'description',
        'result',
        'file_path',
    ];

    public function attention(): BelongsTo
    {
        return $this->belongsTo(HealAttention::class, 'attention_id');
    }
}
