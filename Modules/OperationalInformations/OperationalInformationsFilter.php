<?php
namespace App\Modules\OperationalInformations;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

class OperationalInformationsFilter extends QueryFilter
{

   public function type(string $code) {
       $this->builder->whereHas('type', function($q) use ($code) {
           $q->where('code', $code);
       });
   }

    public function source(int|string $source) {
        $this->builder->whereHas('sources', function (Builder $query) use($source) {
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

    public function tag(string $tag)
    {
        $this->builder->WhereHas('tags', function($query) use ($tag) {
            $query->where('id', $tag)->orWhere("code", $tag);
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
