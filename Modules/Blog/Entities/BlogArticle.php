<?php

namespace Modules\Blog\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Blog\Services\ArticleContentCleaner;

class BlogArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content_text',
        'imagen',
        'views',
        'likes',
        'url',
        'publicity',
        'status',
        'keywords',
        'short_description',
        'category_id',
        'user_id'
    ];

    protected static function newFactory()
    {
        return \Modules\Blog\Database\factories\BlogArticleFactory::new();
    }

    public function getContentTextAttribute($value)
    {
        return html_entity_decode($value, ENT_QUOTES, "UTF-8");
    }

    /**
     * Contenido listo para pintar en la web publica: igual que content_text pero con
     * las listas reparadas, porque el HTML guardado puede venir mal formado.
     */
    public function getContentHtmlAttribute(): string
    {
        return ArticleContentCleaner::cleanLists($this->content_text);
    }

    public function getImagenAttribute($value)
    {
        return ($value != 'img/imagen-no-disponible.jpg' ? asset('storage/' . $value) : asset($value));
    }

    public function getKeywordsAttribute($value)
    {
        return ($value ? json_decode($value) : null);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'article_id');
    }
}
