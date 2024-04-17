<?php

namespace App\Modules\Commissions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use App\Http\Filters\Filterable;

// Realations
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/* Helpers */
use Carbon\Carbon, Str;
use App\Helpers\HString;

/* Models */
use App\Modules\Workers\Worker;
use App\Modules\Departments\Department;
use App\Modules\Directions\Direction;
use App\Modules\Meetings\Meeting;

/* Relations Traits */
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasSources;
use App\Traits\Relations\HasWorkers;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;

class Commission extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasSite, HasAuthors, HasDocuments, HasSources, HasWorkers;

    protected $with = ['creator:id,name,surname,second_name,email,phone,position', 'editor:id,name,surname,second_name,email,phone,position'];

    protected $fillable = [
        'title', 'slug', 'body', 'period_meeting', 'info', 'redirect', 'site_id', 'creator_id', 'editor_id', 'is_published', 'published_at'
    ];

    protected $casts = [
        'published_at'  => 'datetime:Y-m-d H:i:s',
        'is_published'  => 'boolean',
        'body'          => 'array',
        'redirect'      => 'array',
    ];

    public static function boot() {
        parent::boot();
        self::deleted(function($commission) {
            if($commission->isForceDeleting()) {
                $commission->departments()->detach();
                $commission->directions()->detach();
                $commission->documents()->detach();
                $commission->sources()->detach();
                $commission->members()->detach();
                $commission->heads()->detach();
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

    // Methonds
    public function sync_with_sort($model, string $relation, array $items, string $sort_name) {
        $model->$relation()->detach();
        if($items) {
            foreach($items as $index => $item) {
                $model->$relation()->attach($item, [$sort_name => $index+1]);
            }
        }
    }

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

    /**
     * Relations
     */
    public function directions(): MorphToMany {
        return $this->morphedByMany(Direction::class, 'model', 'model_has_commissions', 'commission_id', 'model_id');
    }

    public function departments(): MorphToMany {
        return $this->morphedByMany(Department::class, 'model', 'model_has_commissions', 'commission_id', 'model_id');
    }

    public function meetings(): MorphToMany {
        return $this->morphedByMany(Meeting::class, 'model', 'model_has_commissions', 'commission_id', 'model_id');
    }

    public function heads(): MorphToMany {
        return $this->morphedByMany(Worker::class, 'model', 'commission_has_heads', 'commission_id', 'head_id');
    }

    public function members(): MorphToMany {
        return $this->morphedByMany(Worker::class, 'model', 'commission_has_members', 'commission_id', 'member_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }
}
