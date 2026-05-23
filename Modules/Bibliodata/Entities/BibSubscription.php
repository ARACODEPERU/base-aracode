<?php

namespace Modules\Bibliodata\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibSubscription extends Model
{
    protected $table = 'bib_subscriptions';

    protected $fillable = [
        'plan_id',
        'subscriber_type',
        'user_id',
        'organization_id',
        'book_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BibSubscriptionPlan::class, 'plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(BibOrganization::class, 'organization_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(BibBook::class, 'book_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
