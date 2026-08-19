<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BilleteraDigital extends Model
{
    use HasFactory;

    protected $table = 'billeteras_digitales';

    protected $fillable = [
        'image',
        'short_name',
        'full_name',
        'status',
    ];

    public function companyBilleteras(): HasMany
    {
        return $this->hasMany(CompanyBilletera::class, 'billetera_id');
    }
}
