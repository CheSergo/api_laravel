<?php
namespace App\Modules\Institutions;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

class InstitutionsFilter extends QueryFilter
{
    public function type(int $type) {
        $this->builder->where('type_id', $type);
    }
}
