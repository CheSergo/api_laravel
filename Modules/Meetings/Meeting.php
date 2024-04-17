<?php

namespace App\Modules\Meetings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

// Relations
// use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Filters
use App\Http\Filters\Filterable;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Relations Traits
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;

// Models
use App\Modules\Sites\Site;
use App\Modules\Meetings\MeetingTypes\MeetingType;
use App\Modules\Meetings\Venues\Venue;
use App\Modules\Commissions\Commission;
use App\Modules\Documents\Document;

class Meeting extends Model /*implements HasMedia*/
{
    use HasFactory, SoftDeletes, Filterable /*InteractsWithMedia*/;
    use HasDocuments, HasAuthors, HasSite;

    protected $fillable = [
        'title', 'slug', 'body'
    ];

    protected $casts = [
        'begin_time_at' => 'datetime:Y-m-d H:i:s',
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'video' => 'array',
    ];

    /**
     * Mutators
     */
    public static function boot() {
        parent::boot();
        // удаление связей
        self::deleted(function($meeting) {
            if($meeting->isForceDeleting()) {
                $meeting->commissions()->detach();
                $meeting->documents()->detach();
            }
        });
    }

    /**
     * Преобразователи
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

    public function setBeginTimeAtAttribute($value) {
        $this->attributes['begin_time_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }

 
    // Связи
    public function site(): BelongsTo {
        return $this->belongsTo(Site::class, 'site_id', 'id');
    }

    public function venue(): BelongsTo {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    public function type(): BelongsTo {
        return $this->belongsTo(MeetingType::class, 'type_id');
    }

    public function commissions(): MorphToMany {
        return $this->morphToMany(Commission::class, 'model', 'model_has_commissions', 'model_id', 'commission_id');
    }

    public function agenda(): MorphToMany {
        return $this->morphedByMany(Document::class, 'model', 'meeting_has_agendas', 'meeting_id', 'agenda_id');
    }
    public function protocol(): MorphToMany {
        return $this->morphedByMany(Document::class, 'model', 'meeting_has_protocols', 'meeting_id', 'protocol_id');
    }
    public function transcript(): MorphToMany {
        return $this->morphedByMany(Document::class, 'model', 'meeting_has_transcripts', 'meeting_id', 'transcript_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }
    
}
