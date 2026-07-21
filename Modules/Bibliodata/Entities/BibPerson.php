<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BibPerson extends Model
{
    protected $table = 'people';

    public function author(): HasOne
    {
        return $this->hasOne(BibAuthor::class, 'person_id');
    }
}
