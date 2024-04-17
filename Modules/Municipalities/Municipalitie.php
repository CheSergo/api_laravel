<?php

namespace App\Modules\Municipalities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Filters
use App\Http\Filters\Filterable;
// Relations
use App\Traits\Relations\HasSite;
// Traits
use App\Traits\Relations\HasAuthors;

class Municipalitie extends Model
{
    use HasFactory, SoftDeletes, Filterable, HasSite;
    use HasAuthors;

    protected $casts = [
        'is_published' => 'boolean',
    ];
    
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

}