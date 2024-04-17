<?php

namespace App\Modules\Modules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Relations
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Traits
// use App\Traits\Relations\HasComponents;
use App\Traits\Relations\HasAbilities;

// Models
use App\Modules\Menus\Menu;
use App\Modules\Sites\Site;
use App\Modules\Components\Component;

class Module extends Model
{
    use HasFactory, SoftDeletes;
    use HasAbilities;

    protected $table = 'modules';

    protected $fillable = [
        'title', 'code', 'description'
    ];

    /**
     * Mutators
     */
    public static function boot() {
        parent::boot();
        // удаление связей
        self::deleted(function($module) {
            if($module->isForceDeleting()) {
                $module->sites()->detach();
                $module->menus()->detach();
                $module->components()->detach();
            }
        });
    }

    /**
     * Relations
     */
    public function modulable(): MorphTo {
        return $this->morphTo();
    }

    public function menus(): MorphToMany {
        return $this->morphToMany(Menu::class, 'model', 'model_has_menus');
    }

    public function sites(): MorphToMany {
        return $this->morphedByMany(Site::class, 'model', 'model_has_modules');
    }

    public function components(): HasMany {
        return $this->hasMany(Component::class);
    }

}
