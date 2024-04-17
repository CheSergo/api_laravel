<?php
namespace App\Http\Filters\Common;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Str;

class ResourceFilter extends QueryFilter
{
    
    /**
     * @param int $source
     */
    public function theme(int $theme)
    {
        $this->builder->WhereHas('themes', function($query) use ($theme) {
            $query->where('id', $theme);
        });
    }

    /**
     * @param string $title
     */
    public function search(string $title)
    {
        $this->builder->where('title', 'like', "%$title%");
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