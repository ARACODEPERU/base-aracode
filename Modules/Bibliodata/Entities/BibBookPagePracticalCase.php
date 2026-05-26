<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BibBookPagePracticalCase extends Model
{
    use HasFactory;

    protected $table = 'bib_book_page_practical_cases';

    protected $fillable = [
        'page_id',
        'title',
        'type',
        'content_html',
        'file_path',
        'file_name',
        'file_mime',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    public function page()
    {
        return $this->belongsTo(BibBookPage::class, 'page_id');
    }
}
