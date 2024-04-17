<?php
namespace App\Modules\Documents;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

// Relations
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Filters
use App\Http\Filters\Filterable;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\HString;

// Models
use App\Models\User;
use App\Modules\Sites\Site;
use App\Modules\Sections\Section;
use App\Modules\Articles\Article;
use App\Modules\Directions\Direction;
use App\Modules\Commissions\Commission;
use App\Modules\Documents\DocumentTypes\DocumentType;
use App\Modules\Documents\DocumentIntervals\DocumentInterval;
use App\Modules\Documents\DocumentStatuses\DocumentStatus;
// Traits
use App\Traits\Relations\HasTags;
use App\Traits\Relations\HasSources;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;
use App\Traits\Common\GetSearchParams;
use App\Traits\Utils\Search;

// Media
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Class Document
 * @package App\Models\Documents
 * Документы
 */
class Document extends Model implements HasMedia
{
    use HasTags, HasSources, HasAuthors, HasSite, InteractsWithMedia;
    use HasFactory, SoftDeletes, Filterable, GetSearchParams, Search;

    protected $appends = [/*'table_name',*/'search_params'];

//    public function getTableNameAttribute()
//    {
//        return "documents";
//    }

    protected $fillable = [
        'title', 'numb', 'date', 'date_antimonopoly_expertise', 'date_anticorruption_expertise', 'slug', 'published_at', 'number_day_expertise_antimonopoly', 'number_day_expertise_anticorruption'
    ];

    protected $casts = [
        'date' => 'datetime:Y-m-d H:i:s',
        'date_antimonopoly_expertise' => 'datetime:Y-m-d H:i:s',
        'date_anticorruption_expertise' => 'datetime:Y-m-d H:i:s',
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'is_antimonopoly' => 'boolean',
        'is_anticorruption' => 'boolean',
        'is_mpa' => 'boolean',
    ];

    protected $with = [
        'media', 'type', 'interval', 'tags', 'sources', 'status',
        'creator:id,name,surname,second_name,email,phone,position', 
        'editor:id,name,surname,second_name,email,phone,position'
    ];

    /**
     * Mutators
     */
    public static function boot() {
        parent::boot();
        // удаление связей
        self::deleted(function($document) {
            if($document->isForceDeleting()) {
                $document->directions()->detach();
                $document->sections()->detach();
                $document->articles()->detach();
                $document->sources()->detach();
                $document->tags()->detach();
            }
        });
    }

    public function setTitleAttribute($value) {
        $this->attributes['title'] = Str::ucfirst(HString::rus_quot($value));
    }

    public function setPublishedAtAttribute($value) {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    public function setDateAtAttribute($value) {
        $this->attributes['date'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setDateAntimonopolyExpertiseAtAttribute($value) {
        $this->attributes['date_antimonopoly_expertise'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setDateAnticorruptionExpertiseAtAttribute($value) {
        $this->attributes['date_anticorruption_expertise'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    /**
     * Relations
     */
    public function type(): BelongsTo {
        return $this->belongsTo(DocumentType::class, 'type_id');
    }

    public function status(): BelongsTo {
        return $this->belongsTo(DocumentStatus::class, 'status_id');
    }

    public function interval(): BelongsTo {
        return $this->belongsTo(DocumentInterval::class, 'document_interval_id');
    }

    public function sections(): MorphToMany {
        return $this->morphedByMany(Section::class, 'model', 'model_has_documents', 'document_id', 'model_id');
    }

    public function commissions(): MorphToMany {
        return $this->morphedByMany(Commission::class, 'model', 'model_has_documents', 'document_id', 'model_id');
    }

    public function directions(): MorphToMany {
        return $this->morphedByMany(Direction::class, 'model', 'model_has_documents', 'document_id', 'model_id');
    }

    public function site(): BelongsTo {
        return $this->belongsTo(Site::class, 'site_id', 'id');
    }

    public function articles(): MorphToMany {
        return $this->morphedByMany(Article::class, 'model', 'model_has_documents', 'document_id', 'model_id');
    }

    /**
     * Media
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document_files')->useDisk('document_files');
        $this->addMediaCollection('document_attachments')->useDisk('document_attachments');
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where(function($q) {
            $q->where('is_published', true)->orWhere('is_published', 1);
        })->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    public function scopeIsAnticorruption($query) {
        return $query->where('is_anticorruption', true)->whereHas('type', function ($query) {
            $query->where('is_anticorruption', true);
        });
    }

    public function scopeIsAntimonopoly($query) {
        return $query->where('is_antimonopoly', true)->whereHas('type', function ($query) {
            $query->where('is_antimonopoly', true); // Проект постановления
        });
    }

}
