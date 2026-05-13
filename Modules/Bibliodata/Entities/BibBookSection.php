<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BibBookSection extends Model
{
    use HasFactory;

    protected $table = 'bib_book_sections';

    protected $fillable = [
        'book_id',
        'parent_id',
        'title',
        'order',
    ];

    public function book()
    {
        return $this->belongsTo(BibBook::class, 'book_id');
    }

    public function parent()
    {
        return $this->belongsTo(BibBookSection::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(BibBookSection::class, 'parent_id')->orderBy('order');
    }

    public function pages()
    {
        return $this->hasMany(BibBookPage::class, 'section_id')->orderBy('page_number');
    }
}
