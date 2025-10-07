<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Database\factories\AcaArrivalSourceFactory;

class AcaArrivalSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(AcaStudent::class, 'arrival_source_id');
    }
}
