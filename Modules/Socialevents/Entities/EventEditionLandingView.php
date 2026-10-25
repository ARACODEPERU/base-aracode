<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventEditionLandingView extends Model
{
    protected $table = 'event_edition_landing_views';

    protected $fillable = [
        'edition_id',
        'visitor_hash',
        'country',
        'country_code',
        'region',
        'city',
        'source',
        'referrer_host',
        'referrer',
        'browser',
        'browser_version',
        'platform',
        'device_type',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id', 'id');
    }
}
