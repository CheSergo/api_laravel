<?php

namespace App\Modules\Changelogs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

use App\Http\Filters\Filterable;

// Relations
use Illuminate\Database\Eloquent\Relations\BelongsTo;

//Helper
use App\Helpers\HString;
use Str;

// Traits
use App\Traits\Relations\HasTags;
use App\Traits\Relations\HasAuthors;

// Media
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Changelog extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, Filterable;
    use InteractsWithMedia, HasTags, HasAuthors;

    protected $fillable = [
        'title', 'description', 'body', 'type', 'is_pin', 'is_published', 'views_count', 'pin_date', 'editor_id', 'creator_id'
    ];

    protected $casts = [
        'pin_date'      => 'datetime:Y-m-d H:i:s',
        'is_pin'        => 'boolean',
        'published_at'  => 'datetime:Y-m-d H:i:s',
        'is_published'  => 'boolean',
        'body'          => 'array',
        'views_count'   => 'integer',
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

    public function setPinDateAttribute($value) {
        $this->attributes['pin_date'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    /**
     * Relations
     */
    public function child(): BelongsTo {
        return $this->belongsTo(Changelog::class, 'child_id', 'id');
    }

    /**
     * Scopes
     */
    public function scopePin($query) {
        return $query->where('is_pin', true);
    }

    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    // Media
    public function registerMediaCollections(): void {
        $this->addMediaCollection('changelog_gallery')->useDisk('changelog_gallery');
    }

}
