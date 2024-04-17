<?php

namespace App\Modules\Documents\DocumentTypes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

// Relations
use Illuminate\Database\Eloquent\Relations\HasMany;

// Filters
use App\Http\Filters\Filterable;

// Models
use App\Modules\Documents\Document;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Traits
use App\Traits\Relations\HasAuthors;

class DocumentType extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasAuthors;

    protected $fillable = [
        'title', 'code',
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'is_mpa' => 'boolean',
        'is_status' => 'boolean',
        'is_antimonopoly' => 'boolean',
        'is_anticorruption' => 'boolean',
    ];

    /**
     * Mutators
     */
    public function setTitleAttribute($value) {
        $this->attributes['title'] = Str::ucfirst(Str::of(HString::rus_quot($value)));
    }

    public function setPublishedAtAttribute($value) {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    /**
     * Relations
     */
    public function documents(): HasMany {
        return $this->HasMany(Document::class, 'type_id');
    }

}
