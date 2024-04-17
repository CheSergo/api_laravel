<?php

namespace App\Modules\Departments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

// Filters
use App\Http\Filters\Filterable;

// Relations
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Traits
use App\Traits\Common\GetUser;
use App\Traits\Relations\HasWorkers;
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;
use App\Traits\Attributes\GetLink;
use App\Traits\Common\GetSearchParams;
use App\Traits\Utils\Search;

// Models
use App\Modules\Sites\Site;
use App\Modules\Sections\Section;
use App\Modules\Components\Component;
use App\Modules\Directions\Direction;
use App\Modules\Departments\DepartmentTypes\DepartmentType;

class Department extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasDocuments, HasWorkers, GetUser, HasAuthors, HasSite, GetLink, GetSearchParams, Search;

    protected $with = ['creator:id,name,surname,second_name,email,phone,position', 'editor:id,name,surname,second_name,email,phone,position', 'type'];

    protected $table = 'departments';

    protected $appends = [
        'link',
        /*'table_name',*/
        'search_params',
    ];
//    public function getTableNameAttribute()
//    {
//        return "departments";
//    }

    protected $fillable = [
        'title', 'slug', 'sort', 'parent_id', 'credentials', 'servicies', 'redirect', 'phone', 'email', 'fax', 'address', 'site_id', 'creator_id', 'editor_id', 'type_id', 'is_published', 'published_at', 'schedule', 'path'
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'credentials' => 'array',
        'servicies' => 'array',
        'redirect' => 'array',
        'schedule' => 'array',
    ];

    /**
     * Mutators
     */
    public static function boot() {
        parent::boot();

        // удаление связей
        self::deleted(function($department) {
            if($department->isForceDeleting()) {
                $department->directions()->detach();
                $department->documents()->detach();
                $department->workers()->detach();
            }
        });
    }

    public function setTitleAttribute($value) {
        $this->attributes['title'] = Str::ucfirst(Str::of(HString::rus_quot($value)));
    }

    public function setPublishedAtAttribute($value) {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    private function pathGenerator() {
        $department = Department::find($this->id);
        if($department) {
            $component = Component::where('parameter', $department->type->code)->first();
            if (isset($component) && !empty($component)) {
                if (isset(request()->user()->active_site_id) && !empty(request()->user()->active_site_id)) {
                    $section = Section::thisSite()->Component($component->template)->first();
                } else {
                    $section = Section::thisSiteFront()->Component($component->template)->first();
                }
            }
            $slugs = !empty($section) ? $section->getSlugsNotRecursive($section, []) : '';
            return '/' . collect($slugs)->reverse()->values()->implode('/');
        } 
    }

    public function path(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->pathGenerator(),
        );
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

    public function getSlugsNotRecursive($element) {
        $stack = [];
        $slugs = [];

        $currentElement = $element;
        while ($currentElement !== null) {
            $slugs[] = $currentElement->slug;
            if ($currentElement->parent !== null) {
                $stack[] = $currentElement->parent;
            }
            if (!empty($stack)) {
                $currentElement = array_pop($stack);
            } else {
                $currentElement = null;
            }
        }
    
        return $slugs;
    }

    /**
     * Relations
     */
    public function site(): BelongsTo {
        return $this->belongsTo(Site::class, 'site_id', 'id');
    }

    public function childs(): HasMany {
        return $this->hasMany(Department::class, 'parent_id', 'id')/*->published()*/->orderBy('sort')->with('creator')->with('editor')->with('documents')->with('workers')->with('childs');
    }

    public function children(): HasMany {
        return $this->hasMany(Department::class, 'parent_id', 'id')/*->published()*/->orderBy('sort')->with('children:id,parent_id,title');
    }

    public function front_children(): HasMany {
        return $this->hasMany(Department::class, 'parent_id', 'id')->orderBy('sort')
        ->published()
        ->without('editor')->without('creator')
        ->select(['id', 'title', 'slug', 'site_id', 'is_published', 'sort', 'redirect', 'type_id', 'parent_id'])
        ->with('front_children')
        ->with('workers:id,surname,name,second_name,position,email,phone,slug,sort');
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(Department::class, 'parent_id', 'id')->published();
    }

    public function directions(): MorphToMany {
        return $this->morphToMany(Direction::class, 'model', 'model_has_directions', 'model_id', 'direction_id');
    }

    public function type(): BelongsTo {
        return $this->belongsTo(DepartmentType::class, 'type_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true)->orWhere('is_published', 1);
    }

    public function scopeContainer($query) {
        return $query->where(function($query) {
            $query->where(function($query) {
                $query->whereNull('parent_id');
            })->orWhere('parent_id', 0);
        });
    }

    public function scopeComponent($query, $template) {
        return $query->where('body', 'LIKE', '%"template":"'.$template.'"%');
    }

}
