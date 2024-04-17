<?php
namespace App\Modules\Sites;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class SitesFilter extends QueryFilter
{
    public function date(string $date)
    {
        $this->builder->whereDate('published_at', date('Y-m-d', strtotime($date)));
    }

    public function search(string $search)
    {
        $search = preg_replace('/[\s\+]+/', ' ', trim($search));
        $words = array_filter(explode(' ', $search));
        $this->builder->where(function (Builder $query) use ($words) {
            foreach ($words as $word) {
                $query->where('title', 'like', "%$word%");
                $query->orWhere('domain', 'like', "%$word%");
            }
        });
    }

    public function type(string $type) {
        $this->builder->where('type', $type);
    }

    public function district(string $district) {
        $this->builder->where('district_id', $district);
    }

    public function tag(string $tag)
    {
        $this->builder->WhereHas('tags', function($query) use ($tag) {
            $query->where('slug', $tag);
        });
    }

    public function isActive(string $value)
    {
        if ($value == 'yes') $active = 1 ;
        else $active = 0 ;
        $this->builder->where('is_active', $active);
    }
    
}