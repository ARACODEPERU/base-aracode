<?php

namespace Modules\CMS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\CMS\Database\factories\CmsAdvertisingFactory;

class CmsAdvertising extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];
    
    protected static function newFactory(): CmsAdvertisingFactory
    {
        //return CmsAdvertisingFactory::new();
    }
}
