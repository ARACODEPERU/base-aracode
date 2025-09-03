<?php

namespace Modules\Churchcommunity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Churchcommunity\Database\factories\CiglePlaceEvangelizationFactory;

class CiglePlaceEvangelization extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['description'];

    protected static function newFactory(): CiglePlaceEvangelizationFactory
    {
        //return CiglePlaceEvangelizationFactory::new();
    }
}
