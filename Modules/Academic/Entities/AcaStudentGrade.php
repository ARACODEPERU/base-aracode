<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class AcaStudentGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'student_id',
        'course_id',
        'final_average',
        'approved',
        'observations',
        'created_by',
        'registered_at',
        'edited_by'
    ];

    protected $casts = [
        'final_average' => 'decimal:2',
        'approved' => 'boolean',
        'registered_at' => 'datetime',
        'edited_by' => 'array',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(AcaCapRegistration::class, 'registration_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaStudent::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(AcaStudentGradeDetail::class, 'grade_id');
    }

    public function addEditHistory(int $userId): void
    {
        $history = $this->edited_by ?? [];
        $history[] = [
            'user_id' => $userId,
            'updated_at' => now()->toISOString(),
        ];
        $this->edited_by = $history;
    }
}
