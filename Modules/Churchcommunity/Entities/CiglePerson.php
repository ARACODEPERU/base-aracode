<?php

namespace Modules\Churchcommunity\Entities;

use App\Models\Person;
use App\Models\PersonRelative;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Churchcommunity\Database\factories\CiglePersonFactory;

class CiglePerson extends Person
{
    use HasFactory;

    protected $table = 'people';

    public function relatives(): HasMany
    {
        return $this->hasMany(PersonRelative::class,'person_one_id', 'id');
    }

    public function member(): HasOne
    {
        return $this->hasOne(CigleMember::class, 'person_id', 'id');
    }

    public function believer(): HasOne
    {
        return $this->hasOne(CigleBeliever::class, 'person_id', 'id');
    }
}
