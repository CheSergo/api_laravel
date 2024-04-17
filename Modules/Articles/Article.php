<?php

namespace App\Modules\Articles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Relations
use Illuminate\Database\Eloquent\Relations\MorphToMany;

// Filters
use App\Http\Filters\Filterable;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Media
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
// use App\Traits\Actions\InteractsWithCustomMedia;

// Relations
use App\Traits\Relations\HasTags;
use App\Traits\Relations\HasSources;
use App\Traits\Relations\HasWorkers;
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasSite;
use App\Traits\Relations\HasAuthors;
use App\Traits\Attributes\Clips;
use App\Traits\Common\GetSearchParams;
use App\Traits\Utils\Search;
use App\Traits\Utils\CheckMediaForLog;
// use App\Traits\Utils\TrackAttributeChanges;

// use Laravel\Scout\Searchable;

// Models
use App\Models\User;
use App\Modules\Directions\Direction;
use App\Modules\Articles\Categories\Category;
use App\Modules\Directions\DirectionTypes\DirectionType;

// Logs
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Article extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, /*InteractsWithCustomMedia*/ SoftDeletes, Filterable;
    use HasSite, HasAuthors, HasTags, HasDocuments, HasWorkers, HasSources, Clips, GetSearchParams, Search, LogsActivity, CheckMediaForLog;

    protected $table = 'articles';

    protected $appends = [/*'table_name', */'search_params'];

//    public function getTableNameAttribute()
//    {
//        return $this->table;
//    }

    protected $fillable = [
        'title', 'slug', 'body', 'video', 'creator_id', 'editor_id', 'site_id', 'is_published', 'views_count',
        'is_pin', 'is_portal', 'pin_date', 'published_at', 'deleted_at'
    ];

    protected $casts = [
        'published_at'                  => 'datetime:Y-m-d H:i:s',
        'is_published'                  => 'boolean',
        'pin_date'                      => 'datetime:Y-m-d H:i:s',
        'is_pin'                        => 'boolean',
        'body'                          => 'array',
        'video'                         => 'array',
    ];

    public static function rss_fields() {
        return [
            'title', 'slug', 'body'
        ];
    }

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

    public function setPinDateAttribute($value) {
        if (isset($value)) {
            $this->attributes['pin_date'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
        } else {
            $this->attributes['pin_date'] = null;
        }
    }

    public static function boot() {
        parent::boot();
        // удаление связей
        self::deleted(function($article) {
            if($article->isForceDeleting()) {
                $article->directions()->detach();
                $article->categories()->detach();
                $article->workers()->detach();
                $article->sources()->detach();
                $article->tags()->detach();
            }
        });
    }

    // Methonds
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
    public function directions(): MorphToMany {
        return $this->morphedByMany(Direction::class, 'model', 'model_has_articles', 'article_id', 'model_id');
    }

    public function direction_types(): MorphToMany {
        return $this->morphedByMany(DirectionType::class, 'model', 'model_has_articles', 'article_id', 'model_id');
    }

    public function categories(): MorphToMany {
        return $this->morphToMany(Category::class, 'model', 'model_has_categories', 'model_id', 'category_id');
    }

    /**
     * Scopes
     */
    public function scopeIsMain($query) {
        return $query->where('is_main', true);
    }
    
    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }
    
    public function scopeComponent($query, $template) {
        return $query->where('body', 'LIKE', '%"template":"'.$template.'"%');
    }
    /**
     * Закреплённые новости
     */
    public function scopeIsPin($query) {
        return $query->where('is_pin', true)
            ->where(function($query) {
                $query->where(function($query) {
                    $query->whereNull('pin_date');
                })->orWhere(function($query) {
                    $query->where('pin_date', '>', date('Y-m-d H:i:s'));
                });
            });
    }

    /**
     * Media
     */
    public function registerMediaCollections(): void {
        $this->addMediaCollection('article_posters')->useDisk('article_posters');
        $this->addMediaCollection('article_gallery')->useDisk('article_gallery');
    }

    /**
     * Logs
     */

     public function getInformationForLog()
    {
        $attributesArray = [];
        foreach($this->fillable as $attribute) {
            $attributesArray[$attribute] = $this->$attribute;
        }
        $attributesArray['relations'] = [
            'directions' => $this->direction_types->pluck('id')->toArray(),
            'categories' => $this->categories->pluck('id')->toArray(),
            'documents' => $this->documents->pluck('id')->toArray(),
            'workers' => $this->workers->pluck('id')->toArray(),
            'sources' => $this->sources->pluck('id')->toArray(),
            'tags' => $this->tags->pluck('id')->toArray(),
        ];
        return $attributesArray;
        // dd($attributesArray);
        // return [
        //     "title" => $this->title,
        //     "site_id" => $this->site_id,
        //     "is_published" => $this->is_published,
        //     "body" => $this->body,
        //     "relations" => [
        //         "categories" => $this->categories->pluck( 'title', 'id' ),
        //         "np" => $this->np->pluck( 'title', 'id' ),
        //         "sites" => $this->sites->pluck( 'title', 'id' ),
        //         "documents" => $this->documents->pluck( 'title', 'id' ),
        //         "tags" => $this->tags->pluck( 'title', 'id' ),
        //         "directions" => $this->directions->pluck( 'title', 'id' ),
        //         "resources" => $this->resources->pluck( 'title', 'id' ),
        //         "workers" => $this->workers->pluck( 'surname', 'id' ),
        //     ]
        // ];
    }
 
     public function saveLog(int $id, $item, array $properties, string $name) {
         activity()
             ->causedBy(User::findOrFail($id))
             ->performedOn($item)
             ->withProperties($properties)
             ->log($name);
     }

     public function getActivitylogOptions(): LogOptions
     {
         return LogOptions::defaults()
         ->dontSubmitEmptyLogs();
     }

    //  public function saveDelLog(int $id, $item, array $properties, string $name) {
    //      activity()
    //          ->causedBy(User::findOrFail($id))
    //          ->performedOn($item)
    //          ->withProperties($properties)
    //          ->log($name);
    //  }
}
