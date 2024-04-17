<?php

namespace App\Modules\OperationalInformations\OperationalInformationTypes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Filters
use App\Http\Filters\Filterable;

// Media
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Models
use App\Modules\OperationalInformations\OperationalInformation;

// Traits
use App\Traits\Relations\HasAuthors;

class OperationalInformationType extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, Filterable, InteractsWithMedia;
    use HasAuthors;

    protected $table = 'operational_information_types';

    protected $fillable = [
        'title', 'code'
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
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
    public function oper_infos(): HasMany {
        return $this->hasMany(OperationalInformation::class, 'type_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    public function scopeComponent($query, $template) {
        return $query->where('body', 'LIKE', '%"template":"'.$template.'"%');
    }

    /**
     * Media
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('operinfo_icons')->useDisk('operinfo_icons');
    }
}
