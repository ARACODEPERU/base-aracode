<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibSubscriptionPlan extends Model
{
    protected $table = 'bib_subscription_plans';

    protected $fillable = [
        'name',
        'code',
        'description',
        'scope_type',
        'max_books',
        'duration_type',
        'duration_value',
        'includes_ai_chatbot',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'max_books' => 'integer',
        'duration_value' => 'integer',
        'includes_ai_chatbot' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(BibBook::class, 'bib_subscription_plan_books', 'plan_id', 'book_id')
            ->withTimestamps();
    }

    public function planBooks(): HasMany
    {
        return $this->hasMany(BibSubscriptionPlanBook::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(BibSubscription::class, 'plan_id');
    }
}
