<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AcaStudentParticipation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'module_id',
        'theme_id',
        'content_id',
        'participation_score',
        'teacher_comment',
        'created_by',
        'edited_by',
    ];

    protected $casts = [
        'participation_score' => 'decimal:2',
        'edited_by' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaStudent::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(AcaModule::class, 'module_id');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(AcaTheme::class, 'theme_id');
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(AcaContent::class, 'content_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
