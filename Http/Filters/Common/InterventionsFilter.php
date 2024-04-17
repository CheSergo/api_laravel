<?php
namespace App\Http\Filters\Common;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Str;
use App\Models\User;
use App\Http\Filters\Common\QueryFilter;

class InterventionsFilter extends QueryFilter
{
    /**
     * @param int $resource
     */
    public function resource(int $resource)
    {
        $this->builder->whereHas('resources', function($query) use ($resource) {
            $query->where('id', $resource);
        });
    }

    /**
     * @param string $format
     */
    public function format(string $format)
    {
        $this->builder->where('format', $format);
    }

    /**
     * @param string $date
     */
    public function date(string $date)
    {
        $this->builder->whereDate('intervention_datetime', date('Y-m-d', strtotime($date)));
    }

    /**
     * @param mixed $tags
     */
    public function tags(mixed $tags)
    {
        $this->builder->whereHas('tags', function($query) use ($tags) {
            $tags = is_array($tags) ? $tags : explode(',' , $tags);
            $tag_slug = function($value) {
                return Str::slug($value);
            };
            $query->whereIn('slug', array_map($tag_slug, $tags));
        });
    }


    /**
     * @param int $place
     */
    public function place(int $place)
    {
        $this->builder->whereHas('place', function($query) use ($place) {
            $query->where('id', $place);
        });
    }

    /**
     * @param string $search
     */
    public function search(string $search)
    {
        $words = array_filter(explode(' ', $search));

        $this->builder->where(function (Builder $query) use ($words) {
            foreach ($words as $word) {
                $query->where('title', 'like', "%$word%");
            }
        });
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
        else
            $this->builder->orderBy($sort, $sortType);
    }
}