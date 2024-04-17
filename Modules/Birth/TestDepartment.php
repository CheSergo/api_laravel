<?php

namespace App\Modules\Birth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

// Models
use App\Modules\Sites\Site;
use App\Modules\Directions\Direction;
use App\Modules\Departments\DepartmentTypes\DepartmentType;

class TestDepartment extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasDocuments, HasWorkers, GetUser, HasAuthors, HasSite;

    protected $with = ['creator:id,name,surname,second_name,email,phone,position', 'editor:id,name,surname,second_name,email,phone,position', 'type'];

    protected $table = 'test_departments';
    protected $fillable = [
        'title', 'slug', 'sort', 'parent_id', 'credentials', 'servicies', 'redirect', 'phone', 'email', 'fax', 'address', 'site_id', 'creator_id', 'editor_id', 'type_id'
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'credentials' => 'array',
        'servicies' => 'array',
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
        return $query->where('is_published', true);
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
