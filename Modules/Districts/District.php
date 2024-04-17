<?php

namespace App\Modules\Districts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Media
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Traits\Actions\InteractsWithCustomMedia;

// Relations
use App\Http\Filters\Filterable;
use App\Modules\Sources\Source;
use App\Modules\Sources\SourcesFilter;
use App\Traits\Relations\HasAuthors;

class District extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, Filterable, HasAuthors, HasAuthors, InteractsWithMedia, InteractsWithCustomMedia;

    protected $fillable = ['title', 'code', 'sort',];

    protected $with = ['creator:id,name,surname,second_name,email,phone,position', 'editor:id,name,surname,second_name,email,phone,position'];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'body' => 'array',
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
     * Media
     */
    public function registerMediaCollections(): void {
        $this->addMediaCollection('district_posters')->useDisk('district_posters');
    }

    /**
     * Relations
     */
    public function sources(): HasMany {
        $this->hasMany(Source::class);
    }

}
