<?php

namespace App\Modules\Articles\Categories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Filters
use App\Http\Filters\Filterable;

// Traits
use App\Traits\Relations\HasAuthors;

// Models
use App\Modules\Articles\Article;

class Category extends Model
{
    use HasFactory, SoftDeletes, Filterable, HasAuthors;

    protected $with = ['creator:id,name,surname,second_name,email,phone,position', 'editor:id,name,surname,second_name,email,phone,position'];

    protected $fillable = [
        'title', 'slug'
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
    ];

    /**
     * Mutators
     */
    public function setTitleAttribute($value) {
        $this->attributes['title'] = Str::ucfirst(Str::of(HString::rus_quot($value)));
        $this->attributes['code'] = Str::slug($value, '-');
    }

    public function setPublishedAtAttribute($value) {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    // удаление связей
    public static function boot() {
        parent::boot();
        self::deleted(function($category) {
            if($category->isForceDeleting()) {
                $category->articles()->detach();
            }
        });
    }

    /**
     * Relations
     */
    public function articles(): MorphToMany {
        return $this->morphedByMany(Article::class, 'model', 'model_has_categories', 'category_id', 'model_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    public function scopeThisSite($query) {
        return $query->where('site_id', request()->user()->active_site_id); // id current site
    }
}
