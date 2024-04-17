<?php

namespace App\Modules\Sources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

use App\Http\Filters\Filterable;

// Models
use App\Modules\Atricles\Article;
use App\Modules\Commissions\Commission;
use App\Modules\Districts\District;
use App\Modules\Sites\Site;

// Helpers
use Str;

// Traits
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasDocuments;

class Source extends Model {

    use HasFactory, SoftDeletes, Filterable;
    use HasAuthors, HasDocuments;

    // protected $connection = 'db_astrobl';

    protected $fillable = [
        'title', 'slug', 'link', 'description', 'disctrict_id'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean'
    ];

    /**
     * Mutators
     */
    public function setTitleAttribute($value) {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = Str::slug($value, '-');
    }
    
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    /**
     * Relations
     */
    public function articles(): MorphToMany {
        return $this->morphedByMany(Article::class, 'model', 'model_has_sources', 'source_id', 'model_id');
    }

    public function commissions(): MorphToMany {
        return $this->morphedByMany(Commission::class, 'model', 'model_has_sources', 'source_id', 'model_id');
    }

    public function sites() {
        // return $this->belongsToMany(Site::class, 'site_sources')->withTimestamps();
        return $this->belongsToMany(Site::class, 'site_sources', 'site_id', 'source_id')->withTimestamps();
    }

    public function disctrict() {
        return $this->belongsTo(District::class, 'disctrict_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true);
    }

}
