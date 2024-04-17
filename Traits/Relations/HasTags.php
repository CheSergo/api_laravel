<?php
namespace App\Traits\Relations;

use App\Modules\Tags\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasNp
 * @package App\Traits\Relations
 * Отношение тегов к другим моделям
 */
trait HasTags {

    /**
     * @return mixed
     */
    public function tags(): BelongsToMany
    {
        return $this->morphToMany(Tag::class, 'model', 'model_has_tags', 'model_id', 'tag_id');
    }

}