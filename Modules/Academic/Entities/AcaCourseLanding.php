<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaCourseLanding extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'url_slug',
        'banner_start_date',
        'banner_end_date',
        'banner_duration',
        'banner_language',
        'is_published',
        'professional_section',
        'staff_section',
        'results_section',
        'testimonials_section',
        'study_plan_section',
        'problem_section',
        'investment_section',
        'faq_section',
    ];

    protected $casts = [
        'banner_start_date' => 'date:Y-m-d',
        'banner_end_date' => 'date:Y-m-d',
        'banner_duration' => 'integer',
        'is_published' => 'boolean',
        'professional_section' => 'array',
        'staff_section' => 'array',
        'results_section' => 'array',
        'testimonials_section' => 'array',
        'study_plan_section' => 'array',
        'problem_section' => 'array',
        'investment_section' => 'array',
        'faq_section' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public static function generateSlug($courseName)
    {
        $slug = strtolower(trim($courseName));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    public static function getLanguageOptions()
    {
        return [
            'es' => 'Español',
            'en' => 'Inglés',
            'zh' => 'Mandarín',
        ];
    }

    public function getLanguageLabel()
    {
        return self::getLanguageOptions()[$this->banner_language] ?? $this->banner_language;
    }
}
