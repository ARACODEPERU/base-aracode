<?php

namespace Modules\Bibliodata\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BibTag extends Model
{
    use HasFactory;

    protected $table = 'bib_tags';

    protected $fillable = ['name'];
}
