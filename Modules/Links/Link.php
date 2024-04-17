<?php

namespace App\Modules\Links;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Relations
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Models
use App\Modules\Links\LinkTypes\LinkType;
use App\Modules\Sections\Section;

// Traits
use App\Http\Filters\Filterable;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;
use App\Traits\Attributes\GetLink;

// Media
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Traits\Actions\InteractsWithCustomMedia;

class Link extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, InteractsWithCustomMedia, SoftDeletes, Filterable;
    use HasAuthors, HasSite, GetLink;

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'redirect' => 'array',
    ];
    
    protected $appends = ['link'];
    /**
     * Mutators
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = Str::ucfirst(Str::of(HString::rus_quot($value)));
    }
    
    public function setPublishedAtAttribute($value)
    {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    public function getColorAttribute()
    {
        $media = collect($this->media);
        if (!$media->first()) return '';
        $imagePath = $media->first()->original_url;
        $filename = public_path($imagePath);
        if (!$filename) return '';
        $info = getimagesize($filename);
        switch ($info[2]) {
            case 1:
                $img = imageCreateFromGif($filename);
                break;
            case 2:
                $img = imageCreateFromJpeg($filename);
                break;
            case 3:
                $img = imageCreateFromPng($filename);
                break;
        }

        $width = ImageSX($img);
        $height = ImageSY($img);

        $total_r = $total_g = $total_b = 0;
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $c = ImageColorAt($img, $x, $y);
                $total_r += ($c >> 16) & 0xFF;
                $total_g += ($c >> 8) & 0xFF;
                $total_b += $c & 0xFF;
            }
        }

        $rgb = array(
            round($total_r / $width / $height),
            round($total_g / $width / $height),
            round($total_b / $width / $height)
        );

        $color = '#';
        foreach ($rgb as $row) {
            $color .= str_pad(dechex($row), 2, '0', STR_PAD_LEFT);
        }

        imageDestroy($img);

        return $color;
    }

    /**
     * Relations
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(LinkType::class, 'type_id', 'id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    /**
     * Media
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('link_posters')->useDisk('link_posters');
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
