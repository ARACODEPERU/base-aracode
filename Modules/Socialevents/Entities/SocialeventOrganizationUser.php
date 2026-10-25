<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialeventOrganizationUser extends Model
{
    protected $table = 'socialevent_organization_users';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'profile_key',
        'capabilities',
        'status',
        'permissions_updated_at',
        'invited_by',
        'joined_at',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'joined_at' => 'datetime',
        'permissions_updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(SocialeventOrganization::class, 'organization_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
