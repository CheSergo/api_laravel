<?php
namespace App\Modules\Departments;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

class DepartmentsFilter extends QueryFilter
{
    public function created($date) {
        $dates = explode(',', $date);
        $this->builder->whereBetween('date_start', [date('Y-m-d', strtotime($dates[0])), date('Y-m-d', strtotime($dates[1]))]);
    }
}
