<?php

namespace App\Modules\Users\Roles;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;


// Models
use App\Modules\Users\Roles\Role;
use App\Models\User;

// Filters
use App\Http\Filters\Filterable;

class Ability extends Model 
{
    use SoftDeletes, Filterable;

    protected $table = 'abilities';
    protected $fillable = ['title'];

    public function users(): MorphToMany {
        return $this->morphedByMany(User::class, 'model', 'model_has_abilities', 'ability_id', 'model_id');
    }

     /**
     * @return mixed
     */
    public function roles(): MorphToMany {
        return $this->morphedByMany(Role::class, 'model', 'model_has_abilities', 'ability_id', 'model_id');
    }
}