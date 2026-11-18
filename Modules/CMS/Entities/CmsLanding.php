<?php

namespace Modules\CMS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\CMS\Database\factories\CmsLandingFactory;

class CmsLanding extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'slug',
        'free_courses_count',
        'description_short',
        'description_long',
        'main_image',
        'data_related',
        'data_related2',
        'utm_config',
        'additional_field1',
        'additional_field2',
        'additional_field3',
        'date_start',
        'date_end',
        'menu_id'
    ];

    protected $casts = [
        'data_related' => 'array',
        'data_related2' => 'array',
        'utm_config' => 'array',
        'free_courses_count' => 'integer',
    ];

    // 🔹 Accessor para decodificar JSON automáticamente
    protected function dataRelated(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? json_decode($value, true) : null,
            set: fn ($value) => $value ? json_encode($value) : null,
        );
    }

    // 🔹 (opcional) también puedes hacerlo para data_related2
    protected function dataRelated2(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? json_decode($value, true) : null,
            set: fn ($value) => $value ? json_encode($value) : null,
        );
    }
}
