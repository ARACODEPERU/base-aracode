<?php

namespace Modules\Churchcommunity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Churchcommunity\Database\factories\CigleScheduleFactory;

class CigleSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'description',
        'hour_from',
        'hour_to'
    ];

    protected static function newFactory(): CigleScheduleFactory
    {
        //return CigleScheduleFactory::new();
    }
}
