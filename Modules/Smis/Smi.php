<?php
namespace App\Modules\Smis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Relations
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;
use App\Helpers\HFunctions;
use App\Http\Filters\Filterable;

// Models
use App\Models\User;

// Traits
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;
use App\Traits\Relations\HasSources;
use App\Traits\Attributes\GetLink;
use App\Traits\Utils\TrackAttributeChanges;
use App\Traits\Utils\TrackRelationshipChanges;

// Logs
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Smi extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasAuthors, HasSite, GetLink, HasSources, LogsActivity, TrackAttributeChanges, TrackRelationshipChanges;

    protected $fillable = [
        'title', 'number', 'slug', 'registration_date', 'address', 'domain', 'sort', 'specialization',
        'distribution_type', 'area', 'site_id', 'creator_id', 'editor_id', 'is_published', 'published_at',
    ];


    protected $casts = [
        'registration_date' => 'datetime:Y-m-d',
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
    ];

    /**
     * Mutators
     */
    public static function boot() {
        parent::boot();

        // удаление связей
        self::deleted(function($section) {
            if($section->isForceDeleting()) {
                $section->sources()->detach();
            }
        });
    }

    public function setTitleAttribute($value) {
        $this->attributes['title'] = Str::ucfirst(Str::of(HString::rus_quot($value)));
    }

    public function setPublishedAtAttribute($value) {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    public function setRegistrationDateAttribute($value) {
        $this->attributes['registration_date'] =  Carbon::parse($value)->format('Y-m-d');
    }

    /**
     * Scopes
     */

    public function scopePublished($query) {
        return $query->where('is_published', true);
    }

    /**
     * Logs
     */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->dontSubmitEmptyLogs();
    }

    public function saveLog(int $id, $item, array $properties, string $name) {
        activity()
            ->causedBy(User::findOrFail($id))
            ->performedOn($item)
            ->withProperties($properties)
            ->log($name);
    }
}
