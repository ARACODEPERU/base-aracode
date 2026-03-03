<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaAttendanceLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'course_id',
        'link_code',
        'verification_code',
        'valid_from',
        'valid_until',
        'created_by'
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(AcaContent::class, 'content_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function isValid(): bool
    {
        $now = now();
        return $now->gte($this->valid_from) && $now->lte($this->valid_until);
    }
}
