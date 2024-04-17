<?php
namespace App\Http\Filters\Common;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Str;
use App\Models\Common\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Schema;
class ActivityLogFilter  extends QueryFilter
{
    /**
     * @param $search
     */
    public function search($search)
    {
        $all_logs = ActivityLog::all();
        $classes= $all_logs->unique('subject_type')->pluck('subject_type', 'log_name')->toArray();


        foreach ($classes as   $class)
        {
           $table = app($class)->getTable();
           
           if (isset($table) && Schema::hasTable($table))
           {
               if (Schema::hasColumn($table, 'name')) $searchColumn = 'name';
               else if (Schema::hasColumn($table, 'title')) $searchColumn = 'title';
               else  $searchColumn = false;

               if ($searchColumn)
               {
                   $this->builder->select('activity_log.*')
                       ->leftJoin($table, function($query) use ($table){
                           $query->on($table .'.id', '=', 'activity_log.subject_id');
                       })
                       ->orWhere(function ($query)use ($table, $searchColumn,$search, $class) {
                           $query->where($table.'.'.$searchColumn, 'like',  "%$search%")
                               ->where('activity_log.subject_type',  $class);
                       })
                       ->get();
               }

           }


        }

    }

    /**
     * @param $user_id
     */
    public function user($user_id)
    {
        $this->builder->where('activity_log.causer_id', $user_id);
    }

    /**
     * @param $action
     */
    public function action($action)
    {

        $this->builder->where('activity_log.description', $action);
    }

    /**
     * @param $date
     */
    public function date($date)
    {
        $this->builder->whereDate('activity_log.created_at', Carbon::parse($date)->format('Y-m-d'));
    }

    /**
     * @param $type
     */
    public function type($type)
    {
        $this->builder->where('activity_log.log_name', $type);
    }

    /**
     * @param int $site_id
     */
    public function site(int $site_id)
    {
        $this->builder->where('properties' , 'like', '%"site_id":'.$site_id.','.'%');
    }
    /**
     * @param string $sort
     * @param string $sortType
     */
    public function sort(string $sort, $sortType = 'asc')
    {

      if ($sort == 'description')
      {
          if ($sortType=='asc') $rule = 'case 
              when  description="updated" then 1
              when description="created"  then 2 
              when description="deleted"  then 3 
              else 4 end';
          else
              $rule = 'case 
                  when  description="updated" then 3
                  when description="created"  then 2 
                  when description="deleted"  then 1 
                  else 4 end';
          $this->builder->orderByRaw($rule);
      }
      else if ($sort == 'user')
      {
          $this->builder->orderBy(User::select('surname')
              ->whereColumn('activity_log.causer_id', 'users.id'), $sortType
          )
              ->orderBy(User::select('name')
              ->whereColumn('activity_log.causer_id', 'users.id'), $sortType
          )->get();
      }
      else  $this->builder->orderBy($sort, $sortType);
    }
}