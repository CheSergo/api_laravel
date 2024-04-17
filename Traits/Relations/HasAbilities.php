<?php
namespace App\Traits\Relations;

use App\Modules\Users\Roles\Ability;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasAbilities
 * @package App\Traits\Relations
 * Отношение ролей к другим моделям
 */
trait HasAbilities {

    /**
     * @return mixed
     */
    public function abilities(): BelongsToMany
    {
        return $this->morphToMany(Ability::class, 'model', 'model_has_abilities', 'model_id', 'ability_id');
    }

    // public function hasRole($role) {
    //     if (is_array($role)) {
    //         if (count($this->roles->whereIn('name', $role))) {
    //             return $this;
    //         } else {
    //             return false;
    //         }
    //     } elseif (is_string($role)) {
    //         if (count($this->roles->whereIn('name', [$role]))) {
    //             return $this;
    //         } else {
    //             return false;
    //         }
    //     } else {
    //         return false;
    //     }
    // }
}