<?php

namespace App\Modules\Sites;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Relations
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Notifications\Notifiable;

// Filters
use App\Http\Filters\Filterable;

// Models
use App\Models\User;
use App\Models\Articles\Article;
use App\Modules\Links\LinkTypes\LinkType;
use App\Modules\Modules\Module;
use App\Modules\Sections\Section;
use App\Modules\Contracts\Contract;
use App\Modules\Documents\Document;
use App\Modules\Commissions\Commission;
use App\Modules\Sites\PosAppeals\PosAppeal;
use App\Modules\Sources\Source;
use App\Modules\Sources\SiteSources;

// Traits
use App\Traits\Relations\HasComponents;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSocialNetworks;

// Media
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Site extends Model implements HasMedia
{
    use HasFactory, Notifiable, InteractsWithMedia, SoftDeletes, Filterable;
    use /*HasSources,*/ HasComponents, HasAuthors, HasSocialNetworks;

    protected $connection = 'mariadb';
    
    protected $fillable = [
        'title', 'path'
    ];

    protected $casts = [
        'social_links' => 'array',
        'languages' => 'array',
        'pos_widget' => 'array',
        'privacy_policy' => 'array',
    ];

    /**
     * Mutators and Getters
     */
    public function getTitleCleanAttribute() {
        return strip_tags($this->attributes['title']);
    }

    public function getTitleRawAttribute() {
        return "{$this->attributes['title']}";
    }

    public static function boot() {
        parent::boot();
        // удаление связей
        self::deleted(function($site) {
            if($site->isForceDeleting()) {
                $site->modules()->detach();
                $site->link_types()->detach();
            }
        });
    }

    /**
     * Relations
     */
    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class, 'user_sites')->withTimestamps();
    }
    
    public function sections(): HasMany {
        return $this->hasMany(Section::class);
    }

    public function link_types(): BelongsToMany {
        return $this->belongsToMany(LinkType::class, 'link_site_types')->withTimestamps();
    }

    public function documents(): HasMany {
        return $this->hasMany(Document::class);
    }

    public function commissions(): HasMany {
        return $this->hasMany(Commission::class);
    }

    public function contracts(): HasMany {
        return $this->hasMany(Contract::class);
    }

    public function contract(): HasOne {
        // return $this->hasOne(Contract::class)->where('date_end', '>', Carbon::now());
        return $this->hasOne(Contract::class)->ofMany('date_end', 'max');
    }

    public function modules(): MorphToMany {
        return $this->morphToMany(Module::class,'model', 'model_has_modules');
    }

    public function pos_appeal(): BelongsTo {
        return $this->belongsTo(PosAppeal::class);
    }

    public function sources(): BelongsToMany {
        // $database = $this->getConnection()->getDatabaseName();
        // dd($database);
        return $this->belongsToMany(Source::class, 'site_sources', 'site_id', 'source_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true);
    }

    /**
     * Media
     */
    public function registerMediaCollections(): void {
        $this->addMediaCollection('site_logos')->useDisk('site_logos');
    }

}
