<?php
namespace App\Traits\Relations;

use App\Modules\Workers\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasNp
 * @package App\Traits\Relations
 * Отношение тегов к другим моделям
 */
trait HasWorkers {

    /**
     * @return mixed
     */
    public function workers(): BelongsToMany
    {
        return $this->morphToMany(Worker::class, 'model', 'model_has_workers', 'model_id', 'worker_id')
        // ->where('is_published', true)
        ->withPivot('worker_sort')->orderBy('worker_sort', 'ASC');
    }

}