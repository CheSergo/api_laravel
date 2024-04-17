<?php
namespace App\Modules\Basket;

use Illuminate\Database\Eloquent\Builder;
use phpDocumentor\Reflection\Types\Mixed_;
use App\Http\Filters\QueryFilter;
use Illuminate\Http\Request;

class BasketFilter extends QueryFilter
{
    /**
     * @param string $search
     */
    public function search(string $search)
    {
        $type = \Request::query('type');

        $search = preg_replace('/[\s\+]+/', ' ', trim($search));
        $words = array_filter(explode(' ', $search));
        $this->builder->where(function (Builder $query) use ($words, $type) {
            foreach ($words as $word) {
                if ($type == "workers") {
                    $query->orWhere('surname', 'like', "%$word%")
                    ->orWhere('name', 'like', "%$word%")
                    ->orWhere('second_name', 'like', "%$word%")
                    ->orWhere('position', 'like', "%$word%");
                } else {
                    $query->where('title', 'like', "%$word%");
                }
            }
        });
    }
}