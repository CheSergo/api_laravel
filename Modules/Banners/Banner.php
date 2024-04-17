<?php
namespace App\Modules\Banners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Models

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Media
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

// Traits
use App\Traits\Relations\HasAuthors;

// Filters
use App\Http\Filters\Filterable;

class Banner extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, Filterable;
    use HasAuthors, /* HasSite,*/ InteractsWithMedia;

    protected $table = 'banners';

    protected $fillable = [
        'title', 'code', 'description', 'redirect', 'area', 'sort', 'column', 'site_types', 'creator_id', 'editor_id'
    ];

    protected $casts = [
        'published_at'          => 'datetime:Y-m-d H:i:s',
        'published_expired_at'  => 'datetime:Y-m-d H:i:s',
        'is_published'          => 'boolean',
        'redirect'              => 'array',
        'site_types'            => 'array',
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

    public function setPublishedExpiredAtAttribute($value) {
        if(!is_null($value)) {
            $this->attributes['published_expired_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
        } else {
            $this->attributes['published_expired_at'] = null;
        }
    }

    /**
    * Scopes
    */
    public function scopePublished($query) {
        return $query->where(function($q) {
            return $q->where('is_published', true)->orWhere('is_published', 1);
        })
        ->where(function($qe) {
            return $qe->whereNull('published_at')->orWhere('published_at', '<', date("Y-m-d H:i:s"));
        })
        ->where(function($qeu) {
            return $qeu->whereNull('published_expired_at')->orWhere('published_expired_at', '>', date("Y-m-d H:i:s"));
        });
    }

    /**
     * Media
     */
    public function registerMediaCollections(): void {
        $this->addMediaCollection('banner_posters')->useDisk('banner_posters');
    }


}
