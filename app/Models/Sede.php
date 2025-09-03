<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    protected $fillable = [
        'description',
        'map_script',
        'address',
        'person_id',
        'ubigeo',
        'ubigeo_description',
        'country_id',
        'postal_code'
    ];
}
