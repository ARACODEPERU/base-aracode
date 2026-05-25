<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealProcedure extends Model
{
    protected $fillable = [
        'name',
        'category',
        'description',
        'default_price',
        'currency_type_id',
        'is_consultation',
        'is_active',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'is_consultation' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function charges(): HasMany
    {
        return $this->hasMany(HealPatientCharge::class, 'procedure_id');
    }
}
