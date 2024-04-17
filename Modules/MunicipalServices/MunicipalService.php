<?php

namespace App\Modules\MunicipalServices;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

// Filters
use App\Http\Filters\Filterable;
// Relations
use App\Traits\Relations\HasSite;
// Traits
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasDocuments;
use App\Traits\Common\GetSearchParams;
use App\Traits\Utils\Search;

class MunicipalService extends Model 
{
    use HasFactory, SoftDeletes, Filterable, HasSite;
    use HasAuthors, HasDocuments, GetSearchParams, Search;

    protected $appends = [
        /*'table_name'*/
        'search_params',
    ];

//    public function getTableNameAttribute()
//    {
//        return "municipal_services";
//    }

    protected $fillable = [
        'title', 'link',
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
    ];

    /**
     * Mutators
     */
    public function setPublishedAtAttribute($value) {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}