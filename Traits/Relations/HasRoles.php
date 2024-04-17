<?php
namespace App\Traits\Relations;

use App\Modules\Users\Roles\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasRoles
 * @package App\Traits\Relations
 * Отношение ролей к другим моделям
 */
trait HasRoles {

    /**
     * @return mixed
     */
    public function roles(): BelongsToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }
    
    public function site_roles(): BelongsToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id')->where('site_id', request()->user()->active_site_id);
    }

    public function hasRole($role) {
        if (is_array($role)) {
            if (count($this->roles->whereIn('name', $role))) {
                return $this;
            } else {
                return false;
            }
        } elseif (is_string($role)) {
            if (count($this->roles->whereIn('name', [$role]))) {
                return $this;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}