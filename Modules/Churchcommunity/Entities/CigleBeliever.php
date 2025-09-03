<?php

namespace Modules\Churchcommunity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Churchcommunity\Database\factories\CigleBelieverFactory;

class CigleBeliever extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sede_id',
        'member_id',
        'person_id',
        'accept_Christ',
        'schedule_id',
        'name_red',
        'prayer_reason',
        'evangelization_id',
        'well_known_place',
        'contact_person_id',
        'contact_full_names',
        'user_id',
    ];

    public function getWellKnownPlaceAttribute($value)
    {
        return json_decode($value, true);
    }

    // mutator → se ejecuta al asignar valor
    public function setWellKnownPlaceAttribute($value)
    {
        $this->attributes['well_known_place'] = json_encode($value);
    }
}
