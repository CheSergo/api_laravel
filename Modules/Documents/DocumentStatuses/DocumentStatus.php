<?php
namespace App\Modules\Documents\DocumentStatuses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Traits
use App\Traits\Relations\HasAuthors;

/**
 * Список временных интервалов для отчетов
 */
class DocumentStatus extends Model
{
    use HasFactory, SoftDeletes;
    use HasAuthors;

    protected $fillable = ['title', 'code', 'sort', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true);
    }
    
}
