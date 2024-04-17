<?php
namespace App\Modules\Banners;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

class BannersFilter extends QueryFilter
{
    public function siteTypes(string $type)
    {
        $this->builder->whereJsonContains('site_types', $type);
    }

}
