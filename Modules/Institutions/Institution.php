<?php

namespace App\Modules\Institutions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Relations
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Models
use App\Modules\Institutions\InstitutionTypes\InstitutionType;

// Traits
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasWorkers;
use App\Traits\Relations\HasSite;

// Filters
use App\Http\Filters\Filterable;

class Institution extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasSite, HasAuthors, HasDocuments, HasWorkers;

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'about' => 'array',
    ];

    /**
     * Mutators
     */
    public function setTitleAttribute($value) {
        $this->attributes['title'] = Str::ucfirst(Str::of(HString::rus_quot($value)));
        // $this->attributes['slug'] = Str::slug($value, '-');
    }

    public function setPublishedAtAttribute($value) {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    public static function boot() {
        parent::boot();
        // удаление связей
        self::deleted(function($institution) {
            if($institution->isForceDeleting()) {
                $institution->documents()->detach();
            }
        });
    }
    
    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true);
    }

    public function scopeComponent($query, $template) {
        return $query->where('body', 'LIKE', '%"template":"'.$template.'"%');
    }

    /**
     * Methonds
     */
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
    public function type(): BelongsTo {
        return $this->belongsTo(InstitutionType::class, 'type_id');
    }
}
