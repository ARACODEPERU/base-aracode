<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Bibliodata\Database\factories\BibBookProgressFactory;

class BibBookProgress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];
    
    protected static function newFactory(): BibBookProgressFactory
    {
        //return BibBookProgressFactory::new();
    }
}
