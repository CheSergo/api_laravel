<?php
namespace App\Traits\Relations;

use App\Models\Common\Theme;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasThemes
 * @package App\Traits\Relations
 * Отношение тематик к другим моделям
 */
trait HasThemes {

    /**
     * @return mixed
     */
    public function themes(): BelongsToMany
    {
        return $this->morphToMany(Theme::class, 'model', 'model_has_themes', 'model_id', 'theme_id');
    }

}