<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Academic\Database\factories\AcaStudentHistoryFactory;

class AcaStudentHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'person_id',
        'course_id',
        'module_id',
        'theme_id',
        'content_id',
        'type_content'
    ];

    protected static function newFactory(): AcaStudentHistoryFactory
    {
        //return AcaStudentHistoryFactory::new();
    }
}
