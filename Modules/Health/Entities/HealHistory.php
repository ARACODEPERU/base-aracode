<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Health\Support\LogsActivity;

class HealHistory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'patient_id',
        'history_code'
    ];
    
    protected static function newFactory()
    {
        return \Modules\Health\Database\factories\HealHistoryFactory::new();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HealPatient::class, 'patient_id');
    }

    public function attentions(): HasMany
    {
        return $this->hasMany(HealAttention::class, 'history_id');
    }
}
