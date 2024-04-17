<?php
namespace App\Http\Filters\Common;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Str;
use App\Models\User;

class OfferNewsFilter extends QueryFilter
{

    /**
     * @param string $date
     */
    public function date(string $date)
    {
        $this->builder->whereDate('published_at', date('Y-m-d', strtotime($date)));
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
     *
     */
    public function types($types)
    {
        if (in_array('photo', $types) && in_array('video', $types)) {
            $this->builder->whereHas('media', function ($query) {
                $query->where('collection_name', 'news_all');
            })->orWhereNotNull('video');
        } else {
            if (in_array('photo', $types)) {
                $this->builder->whereHas('media', function ($query) {
                    $query->where('collection_name', 'news_all');
                });
            }
            if (in_array('video', $types)) {
                $this->builder->whereNotNull('video');
            }
        }

    }

    /**
     * @param string $tag
     */
    public function tag(string $tag)
    {
        $this->builder->WhereHas('tags', function($query) use ($tag) {
            $query->where('slug', $tag);
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
        $this->builder->orderBy($sort, $sortType);
    }
}