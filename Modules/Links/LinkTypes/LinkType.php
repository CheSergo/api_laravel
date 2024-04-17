<?php

namespace App\Modules\Links\LinkTypes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Relations
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Models
use App\Modules\Sites\Site;
use App\Modules\Links\Link;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Traits
use App\Traits\Relations\HasAuthors;

class LinkType extends Model
{
    use HasFactory, HasAuthors, SoftDeletes;

    protected $fillable = [
        'title', 'code', 'description'
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean'
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
     * Relations
     */
    public function links(): HasMany {
        return $this->HasMany(Link::class, 'type_id');
    }

    public function sites(): BelongsToMany {
        return $this->belongsToMany(Site::class, 'link_site_types')->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true);
    }
}
