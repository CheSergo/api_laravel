<?php
namespace App\Http\Filters\Common;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Str;
use App\Models\User;

class RegProjectFilter  extends QueryFilter
{

    /**
     * @param string $title
     */
    public function search(string $title)
    {
        $this->builder->where('title', 'like', "%$title%");
    }

    /**
     * @param int $source
     */
    public function np(int $np)
    {
        $this->builder->WhereHas('np', function($query) use ($np) {
            $query->where('id', $np);
        });
    }
    
    /**
     * @param string $value
     */
    public function isPublished(string $value)
    {
        if ($value == 'yes') $active = 1 ;
        else $active = 0 ;
        $this->builder->where('is_published', $active);
    }

    /**
     * @param string $sort
     * @param string $sortType
     */
    public function sort(string $sort, $sortType = 'asc')
    {
        if ($sort == 'user_id')
        {
            $this->builder->orderBy(User::select('surname')
                ->whereColumn('user_id', 'users.id'), $sortType
            )
                ->orderBy(User::select('name')
                    ->whereColumn('user_id', 'users.id'), $sortType
                )->get();
        }
        else $this->builder->orderBy($sort, $sortType);
    }

}