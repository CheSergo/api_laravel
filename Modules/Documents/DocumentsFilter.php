<?php
namespace App\Modules\Documents;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

// Helpers
use Carbon\Carbon, Str;

// Models
use App\Models\User;

class DocumentsFilter extends QueryFilter
{
    public $date;

    /**
     * @param string $type
     */
    public function type(string | int $type) {
        $this->builder->where('type_id', strtolower($type));
    }

    /**
     * @param string $type
    */
    public function status(int|string $status) {
        $this->builder->whereHas('status', function (Builder $query) use($status) {
            $query->where('id', $status)->orWhere("code", $status);
        });
    }

    public function year(string $year) {
        $this->builder->whereYear ('date', $year);
    }

    /**
     * @param string $search
     */
    public function search(string $search) {
        $search = preg_replace('/[\s\+]+/', ' ', trim($search));
        $words = array_filter(explode(' ', strtolower($search)));
        $this->builder->where(function (Builder $query) use ($words) {
            foreach ($words as $word) {
                if ( (strlen($word) > 3) || (is_numeric($word)) ) {
                    $query->where('title', 'like', "%$word%")->orWhere('numb', 'like', "%$word%")->orWhere(function ($q) use($word) {
                        $q->whereHas('type', function (Builder $query) use($word) {
                            $query->where('title', 'like', "%$word%");
                        });
                    });
                }
            }
        });
    }

    public function source(int|string $source) {
        $this->builder->whereHas('sources', function (Builder $query) use($source) {
            $query->where('id', $source)->orWhere("slug", $source);
        });
    }

    /**
     * @param int $resource
     */
    public function resource(int $resource) {
        $this->builder->WhereHas('resources', function($query) use ($resource) {
            $query->where('id', $resource);
        });
    }

    /**
     * @param string $numb
     */
    public function numb(string $numb) {
        $this->builder->where('numb', 'like', "%$numb%");
    }

    /**
     * @param string $date
     */
    public function date(string $date) {
        $this->date = $date;
        $this->builder->whereDate('date', date('Y-m-d', strtotime($date)));
    }

    /**
     * @param mixed $tags
     */
    public function tags(mixed $tags) {
        $this->builder->WhereHas('tags', function($query) use ($tags) {
            $tags = is_array($tags) ? $tags : explode(',' , $tags);
            $tag_slug = function($value) {
                return Str::slug($value);
            };
            $query->whereIn('slug', array_map($tag_slug, $tags));
        });
    }

    /**
     * @param string $tag
     */
    public function tag(string $tag) {
        $this->builder->WhereHas('tags', function($query) use ($tag) {
            $query->where('slug', $tag);
        });
    }

    public function mpa(mixed $value) {   
        $this->builder->when($value === 'y', function ($query) {
            return $query->where('is_mpa', true);
        })->when($value === 'n', function ($query) {
            return $query->where('is_mpa', false);
        });
    }
}
