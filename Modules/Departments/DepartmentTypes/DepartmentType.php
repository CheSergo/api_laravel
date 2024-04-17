<?php

namespace App\Modules\Departments\DepartmentTypes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

// Relations
use Illuminate\Database\Eloquent\Relations\HasMany;

// Models
use App\Modules\Departments\Department;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Traits
use App\Http\Filters\Filterable;
use App\Traits\Relations\HasAuthors;

class DepartmentType extends Model
{
    use HasFactory, SoftDeletes, Filterable, HasAuthors;

    protected $fillable = [
        'title', 'code',
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean'
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
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    /**
     * Relations
     */
    public function departments(): HasMany {
       return $this->hasMany(Department::class, 'type_id');
    }
}
