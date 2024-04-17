<?php
namespace App\Modules\Birth;

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

/* MediaLibrary */
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Test extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, Filterable;
    use HasDocuments, HasComponents, HasAuthors, HasSite;

    protected $table = 'test'; 

    protected $fillable = [
        'title', 'body', 'slug', 'redirect', 'views_count', 'video', 'sort', 'parent_id', 'site_id', 'creator_id', 'editor_id'
    ];

    protected $appends = ['path'];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'video' => 'array',
        'body' => 'array',
    ];

    /**
     * Mutators
     */
    public static function boot() {
        parent::boot();
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

    public function getPathAttribute() {
        $slugs[0] = $this->slug;
        if($this->parent) {
            $slugs[1] = $this->parent->slug;
                
            if($this->parent->parent) {
                $slugs[2] = $this->parent->parent->slug;

                if($this->parent->parent->parent) {
                    $slugs[3] = $this->parent->parent->parent->slug;

                    if($this->parent->parent->parent->parent) {
                        $slugs[4] = $this->parent->parent->parent->parent->slug;

                        if($this->parent->parent->parent->parent->parent) {
                            $slugs[5] = $this->parent->parent->parent->parent->parent->slug;
                        }
                    }
                }
            }
        }
        return '/' . collect($slugs)->reverse()->values()->implode('/');
    }

    public function getBreadcrumbsAttribute() {
        $breadcrumbs[0] = [
            'title' => $this->title,
            'path' => $this->path,
        ];
        if($this->parent) {
            $breadcrumbs[1]['title'] = $this->parent->title;
            $breadcrumbs[1]['path'] = $this->parent->path;
            if($this->parent->parent) {
                $breadcrumbs[2]['title'] = $this->parent->parent->title;
                $breadcrumbs[2]['path'] = $this->parent->parent->path;
                if($this->parent->parent->parent) {
                    $breadcrumbs[3]['title'] = $this->parent->parent->parent->title;
                    $breadcrumbs[3]['path'] = $this->parent->parent->parent->path;
                    if($this->parent->parent->parent->parent) {
                        $breadcrumbs[4]['title'] = $this->parent->parent->parent->parent->title;
                        $breadcrumbs[4]['path'] = $this->parent->parent->parent->parent->path;
                        if($this->parent->parent->parent->parent->parent) {
                            $breadcrumbs[5]['title'] = $this->parent->parent->parent->parent->parent->title;
                            $breadcrumbs[5]['path'] = $this->parent->parent->parent->parent->parent->path;
                        }
                    }
                }
            }
        }
        return collect($breadcrumbs)->reverse()->values();
    }

    /**
     * Scopes
     */
    // public function scopeCurrentSite($query, int $id) {
    //     return $query->where('site_id', $id); // id current site
    // }

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
        // ->published()
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
