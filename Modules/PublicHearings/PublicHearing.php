<?php

namespace App\Modules\PublicHearings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Relations
use App\Http\Filters\Filterable;
use App\Traits\Relations\HasSite;
use App\Traits\Relations\HasSources;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasDocuments;
use App\Traits\Attributes\PublishedAt;

class PublicHearing extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasAuthors, HasDocuments, HasSources, HasSite, PublishedAt;

    protected $table = 'public_hearings';

    protected $fillable = ['title', 'advertisement', 'decision', 'date_start', 'date_end'];

    protected $with = ['creator:id,name,surname,second_name,email,phone,position', 'editor:id,name,surname,second_name,email,phone,position'];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'date_start' => 'datetime:Y-m-d H:i:s',
        'date_end' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'advertisement' => 'array',
        'decision' => 'array',
    ];

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
    public function setDateStartAttribute($value) {
        $this->attributes['date_start'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function setDateEndAttribute($value) {
        $this->attributes['date_end'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public static function boot() {
        parent::boot();
        self::deleted(function($commission) {
            if($commission->isForceDeleting()) {
                $commission->documents()->detach();
                $commission->sources()->detach();
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

    // /**
    //  * Scopes
    //  */
    // public function scopePublished($query) {
    //     return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    // }

}
