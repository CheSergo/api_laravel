<?php
namespace App\Traits\Relations;

use App\Modules\Articles\Article;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasContents
 * @package App\Traits\Relations
 * Отношение событий к другим моделям
 */
trait HasArticles {

    /**
     * @return mixed
     */
    public function articles(): BelongsToMany
    {
        return $this->morphToMany(Article::class, 'model', 'model_has_articles', 'model_id', 'article_id');
    }
}