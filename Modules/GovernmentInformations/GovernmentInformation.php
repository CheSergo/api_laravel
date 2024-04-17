<?php

namespace App\Modules\GovernmentInformations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

// Filters
use App\Http\Filters\Filterable;

// Relations
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Traits
use App\Traits\Relations\HasTags;
use App\Traits\Relations\HasSite;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasSources;
// use App\Traits\Attributes\GetLink;
use App\Traits\Common\GetSearchParams;
use App\Traits\Utils\Search;

// Models
use App\Modules\Sections\Section;
use App\Modules\Components\Component;

class GovernmentInformation extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasTags, HasSite, HasAuthors, HasDocuments, HasSources, GetSearchParams, Search;
    

    protected $table = 'government_informations';

    /**
     * SearchController
     */
    protected $appends = [
        /*'table_name'*/
        'search_params'

    ];
    public function getTableNameAttribute()
    {
        return "government_informations";
    }
    /**
     * end SearchController
     */

    protected $fillable = [
        'title', 'slug', 'body', 'redirect'
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'body' => 'array',
        'redirect' => 'array',
    ];

    /**
     * Mutators
     */
    public static function boot() {
        parent::boot();
        // удаление связей
        self::deleted(function($operinfo) {
            if($operinfo->isForceDeleting()) {
                $operinfo->tags()->detach();
                $operinfo->documents()->detach();
            }
        });
    }

    private function pathGenerator() {
        if (isset(request()->user()->active_site_id) && !empty(request()->user()->active_site_id)) {
            $section = Section::thisSite()->Component("GovernmentInformations")->first();
        } else {
            $section = Section::thisSiteFront()->Component("GovernmentInformations")->first();
        }
        if(!is_null($section)) {
            $slugs = $section->getSlugsNotRecursive($section, []);
            return '/' . collect($slugs)->reverse()->values()->implode('/');
        } else {
            return '';
        }
    }

    public function path(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->pathGenerator(),
        );
    }

    /**
     * Преобразователи
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
     * Methonds
     */
    public function sync_with_sort($model, string $relation, array $items, string $sort_name) {
        $model->$relation()->detach();
        if($items) {
            foreach($items as $index => $item) {
                $model->$relation()->attach($item, [$sort_name => $index+1]);
            }
        }
    }

    /**
     * Relations
     */


    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    public function scopeComponent($query, $template) {
        return $query->where('body', 'LIKE', '%"template":"'.$template.'"%');
    }
}
