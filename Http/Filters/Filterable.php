<?php
namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

/**
 * Trait Filterable
 * @package App\Http\Filters
 */
trait Filterable
{
    /**
     * Фильтр ресурсов
     * @param Builder $builder
     * @param QueryFilter $filter
     */
    public function scopeFilter(Builder $builder, QueryFilter $filter) {
        $filter->apply($builder);
    }
}