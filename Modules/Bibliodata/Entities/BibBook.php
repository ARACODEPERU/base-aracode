<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BibBook extends Model
{
    use HasFactory;

    public const STRUCTURE_CHAPTER_SUBCHAPTER = 'chapter_subchapter';

    public const STRUCTURE_LEVEL_CONTENT = 'level_content';

    protected $table = 'bib_books';

    protected $fillable = [
        'title',
        'code_name',
        'description',
        'author_id',
        'category_id',
        'cover_image',
        'file_path',
        'isbn',
        'pages',
        'status',
        'content_structure',
    ];

    protected $casts = [
        'pages' => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(BibAuthor::class, 'author_id');
    }

    public function category()
    {
        return $this->belongsTo(BibCategory::class, 'category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(BibTag::class, 'bib_book_tags', 'book_id', 'tag_id');
    }

    public function sections()
    {
        return $this->hasMany(BibBookSection::class, 'book_id')->orderBy('order');
    }

    public function isLevelContent(): bool
    {
        return $this->content_structure === self::STRUCTURE_LEVEL_CONTENT;
    }

    public function hasSubSections(): bool
    {
        return $this->sections()->whereNotNull('parent_id')->exists();
    }

    public function canChangeContentStructure(): bool
    {
        return ! $this->hasSubSections();
    }
}
