<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaStudentAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_link_id',
        'student_id',
        'course_id',
        'content_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'registered_at',
        'user_edit_id',
        'observation'
    ];

    protected $casts = [
        'registered_at' => 'datetime',
    ];

    public function attendanceLink(): BelongsTo
    {
        return $this->belongsTo(AcaAttendanceLink::class, 'attendance_link_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaStudent::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(AcaContent::class, 'content_id');
    }

    public function userEdit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_edit_id');
    }
}
