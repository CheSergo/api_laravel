<?php

namespace App\Modules\Contests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Filters
use App\Http\Filters\Filterable;

// Relations
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasSite;
use App\Traits\Relations\HasAuthors;

class Contest extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasSite, HasAuthors, HasDocuments;

    protected $table = 'contests';

    protected $fillable = [
        'title', 'slug',
    ];

    protected $casts = [
        'published_at'  => 'datetime:Y-m-d H:i:s',
        'begin_at'      => 'datetime:Y-m-d H:i:s',
        'end_at'        => 'datetime:Y-m-d H:i:s',
        'is_published'  => 'boolean',
        'is_failed'     => 'boolean',
        'announcement'  => 'array',
        'acceptance'    => 'array',
        'second_phase'  => 'array',
        'results'       => 'array',
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

    public function setBeginAtAttribute($value) {
        $this->attributes['begin_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setEndAtAttribute($value) {
        $this->attributes['end_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function scopePublished($query) {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', date("Y-m-d H:i:s"));
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
}
