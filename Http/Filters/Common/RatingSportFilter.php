<?php
namespace App\Http\Filters\Common;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Str;
use App\Models\Common\TypeList;
use App\Models\User;
use App\Http\Filters\Common\QueryFilter;

class RatingSportFilter extends QueryFilter
{

    public function district($district)
    {
        $this->builder->where('district_id', $district);
    }

    public function week($week)
    {
        $this->builder->where('week', $week);
    }

    public function month($month)
    {
        $this->builder->where('month', $month);
    }

    public function year($year)
    {
        $this->builder->where('year', $year);
    }

    public function search(string $title)
    {
        $this->builder->where('title', 'like', "%$title%");
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
