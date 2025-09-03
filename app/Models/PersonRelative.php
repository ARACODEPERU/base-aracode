<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonRelative extends Model
{
    protected $fillable = [
        'person_one_id',
        'person_two_id',
        'relationship'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class,'person_two_id', 'id');
    }
}
