<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Academic\Database\factories\AcaStudentExamFactory;

class AcaStudentExam extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'exam_id',
        'student_id',
        'duration',
        'punctuation',
        'status',
        'details'
    ];

    protected static function newFactory(): AcaStudentExamFactory
    {
        //return AcaStudentExamFactory::new();
    }
}
