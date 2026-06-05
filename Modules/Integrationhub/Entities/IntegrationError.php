<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IntegrationError extends Model
{
    use HasFactory;

    protected $table = 'integration_errors';

    public $timestamps = false;

    protected $fillable = [
        'message',
        'source',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
