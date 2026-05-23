<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibSubscriptionPlanBook extends Model
{
    protected $table = 'bib_subscription_plan_books';

    protected $fillable = [
        'plan_id',
        'book_id',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BibSubscriptionPlan::class, 'plan_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(BibBook::class, 'book_id');
    }
}
