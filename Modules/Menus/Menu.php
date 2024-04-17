<?php

namespace App\Modules\Menus;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

// Relations
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Models
use App\Modules\Menus\MenuTypes\MenuType;
use App\Modules\Modules\Module;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Scopes
     */
    public function scopeShowed($query) {
        return $query->where('is_show', true);
    }

    /**
     * Relations
     */
    public function children(): HasMany {
        return $this->hasMany(Menu::class, 'parent_id', 'id')->with('children:id,title,path,icon,parent_id')->orderBy('sort', 'ASC');
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(Menu::class, 'parent_id', 'id')->with('parent');
    }

    public function type(): BelongsTo {
        return $this->belongsTo(MenuType::class);
    }

    public function modules(): MorphToMany {
        return $this->morphedByMany(Module::class, 'model', 'model_has_menus');
    }
}
