<?php
namespace App\Traits\Relations;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait HasDistricts
 * @package App\Traits\Relations
 * Отношение районов к другим моделям
 */
trait HasAuthors {

    /**
     * @return mixed
     */
    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function editor(): BelongsTo {
        return $this->belongsTo(User::class, 'editor_id', 'id');
    }

}