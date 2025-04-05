<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Database\factories\AcaListVideoFactory;

class AcaListVideo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'course_id',
        'user_id',
        'total_duration',
        'author_id',
        'status',
        'total_views',
        'total_videos',
        'total_stars',
        'keywords',
    ];

    protected static function newFactory(): AcaListVideoFactory
    {
        //return AcaListVideoFactory::new();
    }

    public function videos(): HasMany
    {
        return $this->hasMany(AcaShortVideo::class, 'list_id');
    }
}
