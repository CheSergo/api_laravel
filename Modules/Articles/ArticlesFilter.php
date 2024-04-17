<?php
namespace App\Modules\Articles;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

class ArticlesFilter extends QueryFilter
{

    public $date;
    public $types;
    // public $cats;
    public $workers;

    public function created($date) {
        $dates = explode(',', $date);
        $this->builder->whereBetween('created_at', [date('Y-m-d', strtotime($dates[0])), date('Y-m-d', strtotime($dates[1]))]);
    }

    public function createdArr($period) {
		if(is_array($period) && count($period) > 1) {
				$this->builder->whereBetween('created_at', [date('Y-m-d', strtotime($period[0])), date('Y-m-d', strtotime($period[1]))]);
		}
    }

    public function date(string $date)
    {
        $this->date = $date;
        $this->builder->whereDate('published_at', date('Y-m-d', strtotime($date)));
    }

    public function directionType(string $direction_type) {
        $this->builder->WhereHas('direction_types', function ($q) use ($direction_type) {
            $q->where('code', $direction_type)->orWhere("id", $direction_type);
        });
    }

    public function source(string $source)
    {
        $this->builder->WhereHas('sources', function($query) use ($source) {
            $query->where('id', $source)->orWhere("slug", $source);
        });
    }

    public function sourcesArr(array $sources)
    {
        if (count($sources)) {
            foreach($sources as $i => $source) {
                if ($i == 0) {
                    $this->builder->whereHas('sources', function (Builder $query) use($source) {
                        $query->where('id', $source)->orWhere("slug", $source);
                    });
                } else {
                    $this->builder->orWhereHas('sources', function (Builder $query) use($source) {
                        $query->where('id', $source)->orWhere("slug", $source);
                    });
                }
            }
        }
    }

    public function category(string $category)
    {
        $this->builder->WhereHas('categories', function($query) use ($category) {
            $query->where('code', $category)->orWhere('id', $category);
        });
    }

    public function categoriesArr(array $categories)
    {
        if (count($categories)) {
            foreach($categories as $i => $category) {
                if ($i == 0) {
                    $this->builder->WhereHas('categories', function($query) use ($category) {
                        $query->where('code', $category)->orWhere('id', $category);
                    });
                } else {
                    $this->builder->orWhereHas('categories', function($query) use ($category) {
                        $query->where('code', $category)->orWhere('id', $category);
                    });
                }
            }
        }
    }

    public function tag(string $tag)
    {
        $this->builder->WhereHas('tags', function($query) use ($tag) {
            $query->where('code', $tag)->orWhere('id', $tag);
        });
    }

    public function tagsArr(array $tags)
    {
        if (count($tags)) {
            foreach($tags as $i => $tag) {
                if ($i == 0) {
                    $this->builder->WhereHas('tags', function($query) use ($tag) {
                        $query->where('code', $tag)->orWhere('id', $tag);
                    });
                } else {
                    $this->builder->orWhereHas('tags', function($query) use ($tag) {
                        $query->where('code', $tag)->orWhere('id', $tag);
                    });
                }
            }
        }
    }
}
