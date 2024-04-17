<?php
namespace App\Modules\Links;

use App\Http\Filters\QueryFilter;

// Models
use App\Models\User;
use App\Modules\Links\LinkTypes\LinkType;

class LinksFilter extends QueryFilter
{
    public function type(int $type) {
        $this->builder->where('type_id', $type);
    }
}