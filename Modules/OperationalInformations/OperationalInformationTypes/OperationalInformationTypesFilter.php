<?php
namespace App\Modules\OperationalInformations\OperationalInformationTypes;

use App\Http\Filters\QueryFilter;

class OperationalInformationTypesFilter extends QueryFilter
{
    public function code(string $code) {
        $this->builder->where('code', $code);
    }

    public function sort(string $sort, $sortType = 'asc')
    {
        $this->builder->orderBy($sort, $sortType);
    }
}
