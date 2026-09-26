<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Modules\Onlineshop\Entities\OnliItem;

class AcaCourse extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'description',
        'slug',
        'usine',
        'course_day',
        'course_month',
        'course_year',
        'category_id',
        'image',
        'modality_id',
        'type_description',
        'teacher_id',
        'sector_description',
        'price',
        'certificate_description',
        'discount',
        'discount_applies',
        'auto_certificate',
        'round_grades',
        'certificate_title',
    ];

    protected $casts = [
        'round_grades' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Ruta amigable: el slug se deriva de la descripcion al crear y se
        // regenera al editar si la descripcion cambio y no se definio uno a mano.
        static::creating(function (self $course) {
            if (blank($course->slug)) {
                $course->slug = $course->generateUniqueSlug($course->description);
            }
        });

        static::updating(function (self $course) {
            if (blank($course->slug) || ($course->isDirty('description') && ! $course->isDirty('slug'))) {
                $course->slug = $course->generateUniqueSlug($course->description, $course->id);
            }
        });
    }

    public function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = 'curso' . ($ignoreId ? '-' . $ignoreId : '');
        }

        $slug = $base;
        $i = 2;

        while (self::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AcaCategoryCourse::class, 'category_id');
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(AcaModality::class, 'modality_id', 'id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(AcaModule::class, 'course_id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(AcaTeacher::class, 'id', 'teacher_id');
    }

    public function brochure(): HasOne
    {
        return $this->hasOne(AcaBrochure::class, 'course_id');
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(AcaAgreement::class, 'course_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(AcaTeacherCourse::class, 'course_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(AcaCapRegistration::class, 'course_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AcaThemeQuestion::class, 'course_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AcaThemeComment::class, 'course_id');
    }

    public function onlitem(): HasOne
    {
        return $this->hasOne(OnliItem::class, 'id', 'item_id');
    }

    public function exams(): HasOne
    {
        return $this->hasOne(AcaExam::class, 'course_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(AcaCertificate::class, 'course_id');
    }

    public function exam(): HasOne
    {
        return $this->hasOne(AcaExam::class, 'course_id');
    }

    public function getStudentsCount(): int
    {
        return $this->registrations()->count();
    }

    public function isPopular(int $threshold = 30): bool
    {
        return $this->registrations()->count() >= $threshold;
    }

    public function landing()
    {
        // 'course_id' es la columna en aca_course_landings que apunta a este curso
        return $this->hasOne(AcaCourseLanding::class, 'course_id');
    }
}
