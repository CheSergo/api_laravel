<?php
namespace App\Modules\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Models
use App\Modules\Sites\Site;
use App\Modules\Sections\Section;
use App\Modules\Components\ComponentField;
use App\Modules\Modules\Module;

// Traits
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;

// Relations
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Filters
use App\Http\Filters\Filterable;

class Component extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    use HasAuthors, HasSite;

    protected $fillable = [
        'name', 'template'
    ];

    public function settings($model_id) {
        $settings = $this->settings_component()->where('model_id', $model_id)->first(['settings']);
        if (!$settings) {
            return null;
        }
        $settings = $settings->toArray()['settings'];
        return collect(json_decode($settings, true))->flatMap(function ($values) {
            return $values;
        });
    }

    /**
     * Relations
     */
    public function fields(): HasMany {
        return $this->hasMany(ComponentField::class);
    }

    public function component_fields(): BelongsToMany {
        return $this->belongsToMany(Site::class, 'component_fields')->withTimestamps();
    }

    public function sections(): MorphToMany {
        return $this->morphedByMany(Section::class, 'model', 'model_has_components', 'component_id', 'model_id');
    }

    // public function modules(): MorphToMany {
    //     return $this->morphedByMany(Module::class, 'model', 'model_has_components', 'component_id', 'model_id');
    // }

    public function module(): BelongsTo {
        return $this->belongsTo(Module::class);
    }
}
