<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url_base',
        'description',
        'execution_type',
        'is_active',
        'config',
        'last_executed_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'last_executed_at' => 'datetime'
    ];

    // protected $hidden = [
    //     'config'
    // ];

    public function auths(): HasMany
    {
        return $this->hasMany(IntegrationAuth::class, 'integration_id');
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(IntegrationEndpoint::class, 'integration_id');
    }

    public function headers(): HasMany
    {
        return $this->hasMany(IntegrationHeader::class, 'integration_id');
    }

    public function queries(): HasMany
    {
        return $this->hasMany(IntegrationQuery::class, 'integration_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(IntegrationSchedule::class, 'integration_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(IntegrationExecLog::class, 'integration_id');
    }
}
