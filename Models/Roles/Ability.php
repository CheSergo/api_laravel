<?php

namespace App\Models\Roles;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use App\Http\Filters\Common\Filterable;

class Ability extends Model {

    use SoftDeletes, Filterable;

    protected $table = 'abilities';

    protected $fillable = ['title'];

    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_abilities', 'ability_id', 'model_id');
    }

     /**
     * @return mixed
     */
    public function roles(): MorphToMany
    {
        return $this->morphedByMany(Role::class, 'model', 'model_has_abilities', 'ability_id', 'model_id');
    }
}