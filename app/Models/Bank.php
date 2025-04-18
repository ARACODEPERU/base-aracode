<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $fillable = [
        'image',
        'short_name',
        'full_name',
        'office_telephone',
        'office_email',
        'office_address',
        'status'
    ];
}
