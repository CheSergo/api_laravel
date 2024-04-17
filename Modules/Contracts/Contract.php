<?php
namespace App\Modules\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Carbon\Carbon;

// Filters
use App\Http\Filters\Filterable;

// Relations Traits
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;

class Contract extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasAuthors, HasSite;

    protected $table = 'contracts';

    protected $fillable = [
        'number', 'date_start', 'date_end', 'comment', 'site_id'
    ];

    protected $casts = [
        'date_start' => 'datetime:Y-m-d H:i:s',
        'date_end' => 'datetime:Y-m-d H:i:s',
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean'
    ];

    
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }
}
