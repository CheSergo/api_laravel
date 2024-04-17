<?php
namespace App\Modules\Meetings\Venues;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

// Helpers
use Str;

class VenuesFilter extends QueryFilter
{

    public function search(string $search)
    {
        $words = array_filter(explode(' ', $search));

        $this->builder->where(function (Builder $query) use ($words) {
            foreach ($words as $word) {
                $query->where('title', 'like', "%$word%");
            }
        });
    }

    public function isPublished(string $value)
    {
        if ($value == 'yes') $active = 1 ;
        else $active = 0 ;
        $this->builder->where('is_published', $active);
    }

    public function sort(string $sort, $sortType = 'asc')
    {
        $this->builder->orderBy($sort, $sortType);
    }
}
