<?php
namespace App\Modules\Instructions;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

class InstructionsFilter extends QueryFilter
{
    public function type(int $type) {
        $this->builder->where('type_id', $type);
    }
}
