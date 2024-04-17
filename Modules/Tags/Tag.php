<?php

namespace App\Modules\Tags;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

// Helpers
use Str;
use App\Helpers\HString;

// Models
use App\Modules\Articles\Article;
use App\Modules\Documents\Document;

// Filters
use App\Http\Filters\Filterable;

class Tag extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = [
        'title', 'code'
    ];

    /**
     * Mutators
     */
    public function setTitleAttribute($value) {
        $this->attributes['title'] = HString::replaceSymbolsHashTag($value);
        $this->attributes['code'] = Str::slug($value, '-');
    }

    /**
     * Relations
     */
    public function articles(): MorphToMany {
        return $this->morphedByMany(Article::class, 'model', 'model_has_tags', 'tag_id', 'model_id');
    }

    public function documents(): MorphToMany {
        return $this->morphedByMany(Document::class, 'model', 'model_has_tags', 'tag_id', 'model_id');
    }
}
