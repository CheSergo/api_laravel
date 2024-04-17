<?php
namespace App\Traits\Relations;

use App\Modules\Sources\Source;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Trait HasSources
 * @package App\Traits\Relations
 * Отношение ресурсов (источников) к другим моделям
 */
trait HasSources {

    // use LogsActivity;

    // public $incrementing = true;
    /**
     * @return mixed
     */
    public function sources(): BelongsToMany
    {
        return $this->morphToMany(Source::class, 'model', 'model_has_sources', 'model_id', 'source_id');
    }

}