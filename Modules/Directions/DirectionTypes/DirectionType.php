<?php

namespace App\Modules\Directions\DirectionTypes;

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
use App\Modules\Directions\Direction;

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
use App\Traits\Relations\HasArticles;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;

// Направления деятельности
class DirectionType extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes, Filterable;
    use HasTags, HasArticles, HasAuthors, HasSite;

    protected $fillable = [
        'title', 'slug', 'body'
    ];

    protected $table = 'directions_types';

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'body' => 'array',
        'video' => 'array',
    ];

    public static function boot() {

        parent::boot();
        // удаление связей
        self::deleted(function($direction) {
            if($direction->isForceDeleting()) {
                $direction->departments()->detach();
            }
        });
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

    /**
     * Scopes
     */
    public function scopeContainer($query) {
        return $query->where(function($query) {
            $query->where(function($query) {
                $query->whereNull('parent_id');
            })->orWhere('parent_id', 0);
        });
    }

    public function scopePublished($query) {
        return $query->where('is_published', true);
    }

    /*
    * Relations
    */
    public function childs(): HasMany {
        return $this->hasMany(DirectionType::class, 'parent_id', 'id')/*->published()*/->orderBy('sort')->with('creator')->with('editor')->with('childs');
    }

    public function children(): HasMany {
        return $this->hasMany(DirectionType::class, 'parent_id', 'id')->select(['id', 'title', 'parent_id'])->orderBy('sort')->with('creator')->with('editor')->with('children');
    }

    public function direction_types(): HasMany {
        return $this->hasMany(DirectionType::class, 'parent_id', 'id')->select(['id','parent_id','title'])->orderBy('sort')
        ->whereHas('directions', function($q) {
            $q->where('site_id', request()->user()->active_site_id);
        })
        ->with('directions', function($qe) {
            $qe->where('site_id', request()->user()->active_site_id)
        ->with('media')
        ->with('documents')
        ->with('childs')
        ->with('creator:id,name,surname,second_name,email,phone,position')
        ->with('editor:id,name,surname,second_name,email,phone,position')
        ->orderBy('sort', 'ASC');
        })->with('direction_types');
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(DirectionType::class, 'parent_id', 'id')->published()->with('parent')->with('media');
    }

    public function site(): BelongsTo {
        return $this->belongsTo(Site::class, 'site_id', 'id');
    }

    public function section(): BelongsTo {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    public function directions(): HasMany {
        return $this->hasMany(Direction::class, 'type_id', 'id');
    }

    // Обратные связи
    public function departments(): MorphToMany {
        return $this->morphedByMany(Department::class, 'model', 'model_has_directions', 'direction_id', 'model_id');
    }

    /**
     * Media
     */
    public function registerMediaCollections(): void {
        $this->addMediaCollection('direction_posters')->useDisk('direction_posters');
    }

}
