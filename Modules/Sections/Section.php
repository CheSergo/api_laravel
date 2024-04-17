<?php
namespace App\Modules\Sections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Relations
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;
use App\Helpers\HFunctions;
use App\Http\Filters\Filterable;

// Models
use App\Models\User;

// Traits
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasComponents;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;
use App\Traits\Attributes\GetLink;
use App\Traits\Attributes\Clips;
use App\Traits\Common\GetSearchParams;
use App\Traits\Utils\Search;

/* MediaLibrary */
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Section extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, Filterable;
    use HasDocuments, HasComponents, HasAuthors, HasSite, GetLink, Clips, GetSearchParams, Search;

    protected $fillable = [
        'title', 'body', 'slug', 'redirect', 'views_count', 'video', 'sort', 'parent_id', 'site_id', 'creator_id', 'editor_id', 'is_published', 'is_show', 'is_deleting_blocked', 'is_editing_blocked', 'published_at', 'reroute', 'path'
    ];

    protected $appends = ['link',/* 'table_name',*/ 'search_params'];

//    public function getTableNameAttribute()
//    {
//        return $this->table;
//    }


    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'is_deleting_blocked' => 'boolean',
        'is_editing_blocked' => 'boolean',
        'video' => 'array',
        'body' => 'array',
        'redirect' => 'array',
    ];

    /**
     * Mutators
     */
    public static function boot() {
        parent::boot();

        static::created(function ($model) {
            $section=Section::find($model->id);
            $slugs = $section->getSlugsNotRecursive($model, []);
            $path = '/' . collect($slugs)->reverse()->values()->implode('/');
            $section->setAttribute('path', $path);
            $section->save();

            $children = $section->childs;
            if (count($children)) {
                foreach ($children as $child) {
                    $child_slugs = $child->getSlugsNotRecursive($child, []);
                    $child_path = '/' . collect($child_slugs)->reverse()->values()->implode('/');
                    $child->timestamps = false;
                    $child->setAttribute('path', $child_path);
             
                    $child->save();
                    $child->timestamps = true;
                }
            }
        });
 
        self::updated(function ($model) {
            $section=Section::find($model->id);
            $slugs = $section->getSlugsNotRecursive($model, []);
            $path = '/' . collect($slugs)->reverse()->values()->implode('/');
            $section->setAttribute('path', $path);
            $section->save();

            $children = $section->childs;
            if (count($children)) {
                foreach ($children as $child) {
                    $child_slugs = $child->getSlugsNotRecursive($child, []);
                    $child_path = '/' . collect($child_slugs)->reverse()->values()->implode('/');
                    $child->timestamps = false;
                    $child->setAttribute('path', $child_path);
             
                    $child->save();
                    $child->timestamps = true;
                }
            }

        });

        // удаление связей
        self::deleted(function($section) {
            if($section->isForceDeleting()) {
                $section->documents()->detach();
                $section->components()->detach();
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

    /**
     * Путь до раздела
     */
    // public function getPathAttribute()  {
    //     $slugs = $this->getSlugsRecursive($this, []);
    //     return '/' . collect($slugs)->reverse()->values()->implode('/');
    // }

    // public function getSlugsRecursive($element, $slugs)  {
    //     $slugs[] = $element->slug;

    //     // Base case: If the current element doesn't have a parent, return the slugs
    //     if($element->parent === null) {
    //         return $slugs;
    //     } 
    //     // Recursive case: If the current element has a parent, call the function again with the parent element
    //     return $this->getSlugsRecursive($element->parent, $slugs);
    // }

    public function getSlugsNotRecursive($element) {
        $stack = [];
        $slugs = [];
    
        $currentElement = $element;
        while ($currentElement !== null) {
            $slugs[] = $currentElement->slug;
            if($currentElement->parent !== null) {
                $stack[] = $currentElement->parent;
            }
            if(!empty($stack)) {
                $currentElement = array_pop($stack);
            } else {
                $currentElement = null;
            }
        }
    
        return $slugs;
    }
    
    /**
     * Хлебные крошки
     */
    public function getBreadcrumbsAttribute() {
        $breadcrumbs = $this->breadcrumbHelper($this);
        return collect($breadcrumbs)->values();
    }

    public function breadcrumbHelper($node, $depth = 0) {
        if($node == null) {
            return [];
        }
        $breadcrumbs = $this->breadcrumbHelper($node->parent, $depth + 1); // Recursive call with parent
        
        // Append current node to breadcrumbs
        $breadcrumbs[$depth] = [
            'title' => $node->title,
            'path' => $node->path,
        ];
        
        return $breadcrumbs;
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
     * Scopes
     */

    public function scopePublished($query) {
        return $query->where('is_published', true);
    }

    public function scopeParentSection($query, int $id) {
        return $query->where('id', $id)->first();
    }

    public function scopeIsShow($query) {
        return $query->where('is_show', 1);
    }

    public function scopeComponent($query, $template) {
        return $query->where('body', 'LIKE', '%"template":"'.$template.'"%');
    }

    /**
     * Relations
     */
    public function children(): HasMany {
        return $this->hasMany(Section::class, 'parent_id', 'id')
        ->published()
        ->orderBy('sort')
        ->with('children:id,title,parent_id');
    }

    public function childs(): HasMany {
        return $this->hasMany(Section::class, 'parent_id', 'id')
        ->orderBy('sort')
        ->withCount('documents')
        ->withCount('media')
        ->with('childs');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Подпункты меню
     */
    public function subitems(): HasMany {
        return $this->hasMany(Section::class, 'parent_id', 'id')->published()->isShow()->orderBy('sort');
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(Section::class, 'parent_id', 'id');
    }

    /**
     * Media
     */
    public function registerMediaCollections(): void {
        $this->addMediaCollection('section_gallery')->useDisk('section_gallery');
    }
}
