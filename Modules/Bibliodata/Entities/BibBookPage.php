<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BibBookPage extends Model
{
    use HasFactory;

    protected $table = 'bib_book_pages';

    protected $fillable = [
        'section_id',
        'page_number',
        'content',
    ];

    protected $casts = [
        'page_number' => 'integer',
    ];

    public function section()
    {
        return $this->belongsTo(BibBookSection::class, 'section_id');
    }
}
