<?php
namespace App\Traits\Relations;

use App\Modules\Documents\Document;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasDocuments
 * @package App\Traits\Relations
 * Отношение документов к другим моделям
 */
trait HasDocuments {

    /**
     * @return mixed
     */
    public function documents(): BelongsToMany
    {
        return $this->morphToMany(Document::class, 'model', 'model_has_documents', 'model_id', 'document_id')->withPivot('document_sort')->orderBy('document_sort', 'ASC');
    }

}