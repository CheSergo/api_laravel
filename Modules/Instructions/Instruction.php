<?php

namespace App\Modules\Instructions;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Media
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

// Filters
use App\Http\Filters\Filterable;

class Instruction extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, SoftDeletes, Filterable;

    protected $fillable = [
        'title', 'slug',
    ];

    protected static $logAttributes = ['title', 'is_published', 'published_at', 'sort', 'parent_id'];
    protected static $logName = 'instruction';

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'description' => 'json',
        'body' => 'array',
    ];

    /**
     * Преобразователи
     */
    public function setTitleAttribute($value) {
        $this->attributes['title'] = Str::ucfirst($value);
        $this->attributes['slug'] = Str::slug($value, '-');
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
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Связи
     */
    public function creator() 
    {
        return $this->belongsTo('\App\Models\User', 'creator_id', 'id');
    }
    public function editor() 
    {
        return $this->belongsTo('\App\Models\User', 'editor_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(Instruction::class, 'parent_id', 'id')->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->with('children')->with('creator')->with('editor');
    }

    public function parent()
    {
        return $this->belongsTo(Instruction::class, 'parent_id', 'id');
    }

    // Media
    public function registerMediaCollections(): void {
        $this->addMediaCollection('instruction_gallery')->useDisk('instruction_gallery');
    }
}
