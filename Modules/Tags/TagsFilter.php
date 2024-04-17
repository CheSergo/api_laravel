<?php
namespace App\Modules\Tags;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

class TagsFilter extends QueryFilter
{
    public function created(string $period) {
        $dates = explode(' - ', $period);
        $this->builder->whereBetween('created_at', [date('Y-m-d', strtotime($dates[0])), date('Y-m-d', strtotime($dates[1]))]);
    }

    public function code(string $search)
    {
        $words = array_filter(explode(' ', $search));
        $this->builder->where(function (Builder $query) use ($words) {
            foreach ($words as $word) {
                $query->where('code', 'like', "%$word%");
            }
        });
    }

}
