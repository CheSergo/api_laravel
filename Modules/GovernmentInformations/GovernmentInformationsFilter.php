<?php
namespace App\Modules\GovernmentInformations;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

class GovernmentInformationsFilter extends QueryFilter
{
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

}
