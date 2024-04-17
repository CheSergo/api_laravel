<?php

namespace App\Modules\OperationalInformations;

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
use App\Modules\OperationalInformations\OperationalInformationTypes\OperationalInformationType;

// Traits
use App\Traits\Relations\HasTags;
use App\Traits\Relations\HasSite;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasSources;
use App\Traits\Attributes\GetLink;
use App\Traits\Common\GetSearchParams;
use App\Traits\Utils\Search;

class OperationalInformation extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasTags, HasSite, HasAuthors, HasDocuments, HasSources, GetLink, GetSearchParams, Search;
    

    protected $table = 'operational_informations';

    protected $appends = [
        'link',
        'table_name',
        'search_params',
    ];
    /**
     * SearchController
     */
    public function getTableNameAttribute()
    {
        return "operational_informations";
    }
    /**
     * end SearchController
     */

    protected $fillable = [
        'title', 'slug', 'body', 'redirect'
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'body' => 'array',
        'redirect' => 'array',
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
        return $this->belongsTo(OperationalInformationType::class, 'type_id');
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
