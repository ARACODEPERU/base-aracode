<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;

class HealTestRecord extends Model
{
    protected $fillable = [
        'batch_code',
        'table_name',
        'record_id',
        'created_by',
    ];
}
