<?php

namespace Modules\Churchcommunity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Churchcommunity\Database\factories\CigleMemberFactory;

class CigleMember extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];
    
    protected static function newFactory(): CigleMemberFactory
    {
        //return CigleMemberFactory::new();
    }
}
