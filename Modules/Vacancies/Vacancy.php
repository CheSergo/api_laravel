<?php

namespace App\Modules\Vacancies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Hekpers
use Carbon\Carbon, Str;
use App\Helpers\HString;
use App\Helpers\HFunctions;

// Filters
use App\Http\Filters\Filterable;

// Models
use App\Modules\Sites\Site;

// Traits
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasAuthors;

class Vacancy extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasDocuments, HasAuthors;

    protected $fillable = [
        'title', 'body',
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'body' => 'array',
    ];

    /**
     * Преобразователи
     */
    public function setTitleAttribute($value) {
        $this->attributes['title'] = HString::rus_quot($value);
    }
    public function setPublishedAtAttribute($value) {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    public function setBeginAtAttribute($value) {
        if (isset($value)) $this->attributes['begin_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
        else $this->attributes['begin_at'] = null;
    }
    public function setEndAtAttribute($value) {
        if (isset($value))   $this->attributes['end_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
        else $this->attributes['end_at'] = null;
    }

    /**
     * Скуопы
     */
    public function scopeThisSite($query) {
        return $query->where('site_id', request()->user()->active_site_id); // id current site
    }

    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    /**
     * Связи
     */
    public function site() {
        return $this->belongsTo(Site::class, 'site_id', 'id');
    }
    // public function creator()
    // {
    //     return $this->belongsTo('\App\Models\User', 'creator_id', 'id');
    // }

    // public function editor()
    // {
    //     return $this->belongsTo('\App\Models\User', 'editor_id', 'id');
    // }

    

}
