<?php
namespace App\Modules\Smis;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

// Helpers
use Str;

class SmisFilter extends QueryFilter
{
    public function date(string $date)
    {
        $this->builder->whereDate('published_at', date('Y-m-d', strtotime($date)));
    }

    public function tag(string $tag)
    {
        $this->builder->WhereHas('tags', function($query) use ($tag) {
            $query->where('slug', $tag);
        });
    }

}