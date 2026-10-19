<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Academic\Database\factories\AcaSubscriptionTypeFactory;

class AcaSubscriptionType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'usine',
        'description',
        'details',
        'prices',
        'status',
        'period',
        'order_number',
        'access_all_courses',
        'access_specialization_programs',
        'access_all_webinars',
        'can_view_specialization_zooms',
        'can_view_specialization_webinars'
    ];

    protected $casts = [
        'details' => 'array',
        'prices' => 'array',
        'access_all_courses' => 'boolean',
        'access_specialization_programs' => 'boolean',
        'access_all_webinars' => 'boolean',
        'can_view_specialization_zooms' => 'boolean',
        'can_view_specialization_webinars' => 'boolean',
    ];
}
