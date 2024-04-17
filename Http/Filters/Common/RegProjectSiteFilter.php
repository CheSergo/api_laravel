<?php
namespace App\Http\Filters\Common;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Str;
use App\Models\Common\RegProject;
use App\Models\User;

class RegProjectSiteFilter  extends QueryFilter
{

    /**
     * @param string $title
     */
    public function search(string $title)
    {
         $this->builder->whereHas('reg_projects' , function ($query) use ($title){
            $query->published()->where('title', 'like', "%$title%");
        })->get();
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
        if ($sort == 'reg_project_name') {
            $this->builder->orderBy(RegProject::select('title')
                ->whereColumn('reg_project_sites.reg_project_id', 'reg_projects.id'), $sortType
            )->get();
        }
        else if ($sort == 'user_id')
        {
            $this->builder->orderBy(User::select('surname')
                ->whereColumn('user_id', 'users.id'), $sortType
            )
                ->orderBy(User::select('name')
                    ->whereColumn('user_id', 'users.id'), $sortType
                )->get();
        }
       else {
           $this->builder->orderBy($sort, $sortType)->get();
       }

    }
}