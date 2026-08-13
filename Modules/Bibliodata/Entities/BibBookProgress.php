<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Bibliodata\Database\factories\BibBookProgressFactory;

class BibBookProgress extends Model
{
    use HasFactory;

    protected $table = 'bib_book_progress';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'section_id',
        'page_id',
        'progress',
        'last_viewed_at',
    ];

    protected $casts = [
        'progress' => 'float',
        'last_viewed_at' => 'datetime',
    ];

    protected static function newFactory(): BibBookProgressFactory
    {
        //return BibBookProgressFactory::new();
    }
}
