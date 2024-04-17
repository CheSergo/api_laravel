<?php
namespace App\Modules\Documents\DocumentIntervals;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Helpers
use Carbon\Carbon;

// Traits
use App\Traits\Relations\HasAuthors;

/**
 * Список временных интервалов для отчетов
 */
class DocumentInterval extends Model
{
    use HasFactory, SoftDeletes;
    use HasAuthors;

    protected $fillable = ['title', 'code', 'sort'];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Mutators
     */
    public function setPublishedAtAttribute($value) {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
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
