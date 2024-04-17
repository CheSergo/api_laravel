<?php
namespace App\Traits\Relations;

use App\Modules\Components\Component;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasComponents
 * @package App\Traits\Relations
 * Отношение компонентов к другим моделям
 */
trait HasComponents {

    /**
     * @return mixed
     */
    public function components(): BelongsToMany
    {
        return $this->morphToMany(Component::class, 'model', 'model_has_components', 'model_id', 'component_id');
    }

}