<?php
namespace App\Traits\Relations;

use App\Models\Districts\District;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasDistricts
 * @package App\Traits\Relations
 * Отношение районов к другим моделям
 */
trait HasDistricts {

    /**
     * @return mixed
     */
    public function districts(): BelongsToMany
    {
        return $this->morphToMany(District::class, 'model', 'model_has_districts', 'model_id', 'district_id');
    }

}