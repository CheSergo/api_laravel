<?php
namespace App\Modules\InformationSystems;

use Illuminate\Database\Eloquent\Builder;
use phpDocumentor\Reflection\Types\Mixed_;
use App\Http\Filters\QueryFilter;

class InformationSystemsFilter extends QueryFilter
{
    public function ownerSource(int $owner_id) {
        $this->builder->where('owner_id', $owner_id);
    }
}
