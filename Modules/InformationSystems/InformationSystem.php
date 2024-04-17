<?php

namespace App\Modules\InformationSystems;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Filters
use App\Http\Filters\Filterable;

// Relations
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Models
use App\Modules\Sources\Source;

// Traits
use App\Traits\Relations\HasSite;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasDocuments;

class InformationSystem extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasSite, HasAuthors, HasDocuments;

    protected $table = 'information_systems';

    protected $fillable = [
        'title', 'short_title','slug', 'exploitation_year', 'link', 'certificate_date', 'description', 'owner_id'
    ];

    protected $casts = [
        'certificate_date' => 'datetime:Y-m-d H:i:s',
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
    ];

    /**
     * Mutators
     */
    public static function boot() {
        parent::boot();
        // удаление связей
        self::deleted(function($operinfo) {
            if($operinfo->isForceDeleting()) {
                $operinfo->tags()->detach();
                $operinfo->documents()->detach();
            }
        });
    }

    /**
     * Преобразователи
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

    public function setCertificateDateAttribute($value) {
        $this->attributes['certificate_date'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
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
    public function information_systems() {
        return $this->belongsToMany(InformationSystem::class, 'information_system_has', 'information_system_id', 'related_system_id');
    }

    public function owner(): BelongsTo {
        return $this->belongsTo(Source::class, 'owner_id', 'id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    public function scopeComponent($query, $template) {
        return $query->where('body', 'LIKE', '%"template":"'.$template.'"%');
    }

}
