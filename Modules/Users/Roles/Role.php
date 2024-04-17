<?php

namespace App\Modules\Users\Roles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

// Трейты
use App\Traits\Relations\HasAbilities;

// Filters
use App\Http\Filters\Filterable;

class Role extends Model {

    use SoftDeletes, HasFactory, Filterable;
    use HasAbilities;

    protected $table = 'roles';

    protected $fillable = ['name', 'title', 'guard_name'];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
    ];

    public function users(): MorphToMany
    {
        return $this->morphedByMany(Role::class, 'model', 'model_has_tags', 'tag_id', 'model_id');
    }

        /**
     * @param $query
     * @return mixed
     */
    public function scopeThisSite($query)
    {
        return $query->where('site_id', request()->user()->active_site_id); // id current site
    }
}