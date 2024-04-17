<?php

namespace App\Modules\Institutions\InstitutionTypes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasMany;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Models
use App\Modules\Institutions\Institution;

// Filters
use App\Http\Filters\Filterable;

// Traits
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;

/**
 * Class InstitutionType
 * @package App\Modules\Institutions\InstitutionTypes
 * Типы подведов
 */
class InstitutionType extends Model
{
    use HasFactory, SoftDeletes, HasSite, HasAuthors, Filterable;

    protected $fillable = [
        'title', 'slug'
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

    /**
     * Relations
     */
    public function institutions(): HasMany {
        return $this->HasMany(Institution::class,  'type_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }
}
