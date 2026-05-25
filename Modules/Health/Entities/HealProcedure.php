<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Health\Support\LogsActivity;

class HealProcedure extends Model
{
    use SoftDeletes;
    use LogsActivity;
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
