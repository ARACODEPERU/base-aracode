<?php

namespace Modules\CMS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Modules\CMS\Database\factories\CmsTestimonyFactory;
use Modules\Onlineshop\Entities\OnliItem;

class CmsTestimony extends Model
{
    use HasFactory;

    public const SOURCE_CMS = 'cms';
    public const SOURCE_STUDENT = 'student';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Ventana (en horas) durante la cual el alumno puede editar su testimonio.
     */
    public const EDIT_WINDOW_HOURS = 15;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'item_id',
        'item_label',
        'entitie',
        'student_id',
        'course_id',
        'title',
        'description',
        'rating',
        'source',
        'approval_status',
        'approved_at',
        'approved_by',
        'author_name',
        'author_role',
        'consent_accepted',
        'consent_accepted_at',
        'consent_version',
        'ia_corrected_at',
        'ia_correction_mode',
        'image',
        'video',
        'status'
    ];

    protected $casts = [
        'rating' => 'integer',
        'status' => 'boolean',
        'consent_accepted' => 'boolean',
        'approved_at' => 'datetime',
        'consent_accepted_at' => 'datetime',
        'ia_corrected_at' => 'datetime',
    ];

    protected $charset = 'utf8mb4';
    protected $collation = 'utf8mb4_unicode_ci';

    protected static function newFactory(): CmsTestimonyFactory
    {
        //return CmsTestimonyFactory::new();
    }

    protected static function boot()
    {
        parent::boot();

        // Se ejecuta antes de guardar un nuevo registro o actualizar uno existente
        static::saving(function ($testimony) {
            $testimony->description = htmlspecialchars((string) $testimony->description, ENT_QUOTES);
            $testimony->video = htmlspecialchars((string) $testimony->video, ENT_QUOTES);
        });

        // Se ejecuta después de recuperar un registro de la base de datos
        static::retrieved(function ($testimony) {
            $testimony->description = htmlspecialchars_decode((string) $testimony->description, ENT_QUOTES);
            $testimony->video = htmlspecialchars_decode((string) $testimony->video, ENT_QUOTES);
        });
    }
    public function getDescriptionAttribute($value)
    {
        return htmlspecialchars_decode((string) $value, ENT_QUOTES);
    }
    public function getVideoAttribute($value)
    {
        return htmlspecialchars_decode((string) $value, ENT_QUOTES);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(OnliItem::class, 'item_id');
    }

    /**
     * Alumno real que dejo el testimonio (null en editoriales, seed o los creados por el admin).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaStudent::class, 'student_id');
    }

    /**
     * Curso al que se refiere el testimonio.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    /**
     * Momento hasta el que el alumno puede seguir editando su testimonio.
     */
    public function editableUntil()
    {
        return $this->created_at ? $this->created_at->copy()->addHours(self::EDIT_WINDOW_HOURS) : null;
    }

    public function isEditableByStudent(): bool
    {
        $until = $this->editableUntil();

        return $until !== null && now()->lessThanOrEqualTo($until);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED && (bool) $this->status;
    }
}
