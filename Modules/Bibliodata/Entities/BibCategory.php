<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Bibliodata\Database\factories\BibCategoryFactory;

class BibCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name', 'description'
    ];

    protected static function newFactory(): BibCategoryFactory
    {
        //return BibCategoryFactory::new();
    }
}
