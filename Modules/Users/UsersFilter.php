<?php
namespace App\Modules\Users;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

// Helpers
use Carbon\Carbon, Str;

// Models

class UsersFilter extends QueryFilter
{

    /**
     * @param string $value
     */
    public function search(string $search)
    {
        $search = preg_replace('/[\s\+]+/', ' ', trim($search));
        $words = array_filter(explode(' ', $search));
        foreach ($words as $word)
        {
            $this->builder->where(function (Builder $query) use ($word) {
                $query->orWhere('name', 'like', "%$word%")
                    ->orWhere('surname', 'like', "%$word%")
                    ->orWhere('second_name', 'like', "%$word%")
                    ->orWhere('email', 'like', "%$word%");
            });
        }
    }


    /**
     * @param int $id
     */
    public function roles(int $id)
    {
        $this->builder->WhereHas('roles', function($query) use ($id) {
            $query->where('id', $id);
        });
    }

    /**
     * @param int $id
     */
    public function site(int $id)
    {
        $this->builder->whereHas('sites', function($query) use ($id) {
            $query->where('id', $id);
        });
    }

    /**
     * @param string $value
     */
    public function isBlocked(string $value)
    {
        if ($value == 'no') $active = 1 ;
        else $active = 0 ;
        $this->builder->where('is_blocked', $active);
    }

}