<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialeventOrganization extends Model
{
    protected $table = 'socialevent_organizations';

    protected $fillable = [
        'name',
        'is_legacy',
        'status',
        'trial_started_at',
        'trial_days',
        'trial_ends_at',
        'access_until',
        'read_permissions',
        'match_rate',
        'prepaid_balance',
        'seat_limit',
        'created_by',
    ];

    protected $casts = [
        'is_legacy' => 'boolean',
        'trial_started_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'access_until' => 'datetime',
        'trial_days' => 'integer',
        'match_rate' => 'decimal:2',
        'prepaid_balance' => 'decimal:2',
        'seat_limit' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class, 'socialevent_organization_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EvenEvent::class, 'organization_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(EventTeam::class, 'organization_id');
    }
}
