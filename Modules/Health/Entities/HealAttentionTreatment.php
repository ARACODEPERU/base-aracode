<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealAttentionTreatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attention_id',
        'treatment_type',
        'name',
        'description',
        'indications',
        'endodontic_data',
        'pharmacological_data',
    ];

    protected $casts = [
        'endodontic_data' => 'array',
        'pharmacological_data' => 'array',
    ];

    public function attention(): BelongsTo
    {
        return $this->belongsTo(HealAttention::class, 'attention_id');
    }
}
