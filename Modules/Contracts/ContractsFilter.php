<?php
namespace App\Modules\Contracts;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

class ContractsFilter extends QueryFilter
{
    public function search(string $search) {
        $search = preg_replace('/[\s\+]+/', ' ', trim($search));
        $numbers = array_filter(explode(' ', $search));

        $this->builder->where(function (Builder $query) use ($numbers) {
            foreach ($numbers as $number) {
                $query->where('number', 'like', "%$number%");
            }
        });
    }

    public function created($date) {
        $dates = explode(',', $date);
        $this->builder->whereBetween('date_start', [date('Y-m-d', strtotime($dates[0])), date('Y-m-d', strtotime($dates[1]))]);
    }

}
