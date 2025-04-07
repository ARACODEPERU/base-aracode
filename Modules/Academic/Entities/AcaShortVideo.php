<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Academic\Database\factories\AcaShortVideoFactory;

class AcaShortVideo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'list_id',
        'title',
        'video',
        'link',
        'duration',
        'stars',
        'author_id',
        'user_id',
        'keywords',
        'status',
        'number_views'
    ];

    protected static function newFactory(): AcaShortVideoFactory
    {
        //return AcaShortVideoFactory::new();
    }
}
