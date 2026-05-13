<?php

namespace Modules\Bibliodata\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BibAuthor extends Model
{
    use HasFactory;

    protected $table = 'bib_authors';

    protected $fillable = [
        'person_id',
        'biography',
        'specialty',
        'webpage'
    ];

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function books()
    {
        return $this->hasMany(BibBook::class, 'author_id');
    }
}
