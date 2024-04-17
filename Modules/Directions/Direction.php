<?php

namespace App\Modules\Directions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Filters
use App\Http\Filters\Filterable;

// Models
use App\Modules\Sites\Site;
use App\Modules\Sections\Section;
use App\Modules\Departments\Department;
use App\Modules\Directions\DirectionTypes\DirectionType;

// Media
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;
use App\Helpers\HFunctions;

// Traits
use App\Traits\Relations\HasTags;
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;
use App\Traits\Attributes\GetLink;
use App\Traits\Attributes\Clips;
use App\Traits\Common\GetSearchParams;
use App\Traits\Utils\Search;

// Направления деятельности
class Direction extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes, Filterable;
    use HasTags, HasDocuments, HasAuthors, HasSite, GetLink, Clips, GetSearchParams, Search;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title', 'slug', 'body', 'redirect', 'video', 'sort', 'type_id', 'section_id', 'site_id', 'creator_id', 'editor_id', 'parent_id', 'is_published', 'views_count', 'created_at', 'published_at', 'is_deleting_blocked', 'is_editing_blocked', 'reroute'
    ];

    protected $appends = [
        'link',
        /*'table_name',*/
        'search_params',
    ];

//    public function getTableNameAttribute()
//    {
//        return "directions";
//    }

    /**
     * @var string[]
     */
    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'is_editing_blocked' => 'boolean',
        'is_deleting_blocked' => 'boolean',
        'body' => 'array',
        'video' => 'array',
        'redirect' => 'array',
    ];

    public static function boot()
    {

        parent::boot();
        // удаление связей
        self::deleted(function ($direction) {
            if ($direction->isForceDeleting()) {
                $direction->documents()->detach();
                $direction->departments()->detach();
            }
        });
    }

    /**
     * Mutators
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = Str::ucfirst(Str::of(HString::rus_quot($value)));
    }

    public function setPublishedAtAttribute($value)
    {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    /**
     * Scopes
     */
    public function scopeContainer($query)
    {
        return $query->where(function ($query) {
            $query->where(function ($query) {
                $query->whereNull('parent_id');
            })->orWhere('parent_id', 0);
        });
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
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

    /*
    * Relations
    */
    public function childs(): HasMany
    {
        return $this->hasMany(Direction::class, 'parent_id', 'id')/*->published()*/->orderBy('sort')->with('creator')->with('editor')->with('documents')->with('childs');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Direction::class, 'parent_id', 'id')->select(['id', 'title', 'parent_id'])->orderBy('sort')->with('children');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Direction::class, 'parent_id', 'id')->published()->with('parent')->with('media');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id', 'id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    // Обратные связи
    public function departments(): MorphToMany
    {
        return $this->morphedByMany(Department::class, 'model', 'model_has_directions', 'direction_id', 'model_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DirectionType::class, 'type_id', 'id');
    }

    /**
     * Media
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('direction_posters')->useDisk('direction_posters');
        $this->addMediaCollection('direction_gallery')->useDisk('direction_gallery');
    }

}
