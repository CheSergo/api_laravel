<?php

namespace App\Modules\Menus\MenuTypes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Relations
use Illuminate\Database\Eloquent\Relations\HasMany;

// Models
use App\Modules\Menus\Menu;

class MenuType extends Model
{
    use HasFactory;

    protected $table = 'menu_types';

    protected $fillable = [
        'title', 'code', 'description'
    ];


    /**
     * Relations
     */
    public function menus(): HasMany {
        return $this->hasMany(Menu::class, 'type_id', 'id');
    }
}
