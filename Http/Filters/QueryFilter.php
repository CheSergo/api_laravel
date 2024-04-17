<?php
namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class QueryFilter
 * @package App\Http\Filters
 */
abstract class QueryFilter
{
    /**
     * @var Array
     */
    protected $query;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $query = rawurldecode($request->server->get('QUERY_STRING'));
        $query = preg_replace('/[\s\+]+/', ' ', $query);
	    if($query) {
            $this->query = $this->keys($query);
	    } else {
		    $this->query = [];
	    }
    }

    /**
     * @param Builder $builder
     */
    public function apply(Builder $builder)
    {
        $this->builder = $builder;

        foreach ($this->fields() as $field => $value) {
            $method = Str::camel($field);

            if (method_exists($this, $method)) {
                
                if (array_key_exists ('sort', $this->fields()) && array_key_exists ('sort_type', $this->fields()))
                {
                   
                    call_user_func_array([$this, $method], array($value, $this->fields()['sort_type']));
                }
                else if (is_array($value)){
                    call_user_func([$this, $method], (array)$value);
                }
                else call_user_func_array([$this, $method], (array)$value);
            }
        }
    }

    /**
     * @return array
     */
    protected function fields(): array
    {
        return array_filter($this->query);
    }


    /**
     * @param string $query
     * Если в запросе есть повторяющиеся ключи переделываем их в массив
     */
    public function keys(string $query): array
    {
	    $result = [];
	    $queries = explode('&', $query);
	    foreach ($queries as $i => $q) {
		    $param = explode('=', $q);
		    if(isset($param[1])) {
			    $result[$param[0]][$i] = $param[1];
		    }
	    }
	    foreach ($result as $k => $v) {
		    if(substr($k, -4) !== '_arr') { //count($result[$k]) < 2
			    $result[$k] = implode(',', $result[$k]);
		    } else {
			    $result[$k] = array_values($result[$k]);
		    }
	    }
	    return $result;
    }

    public function published(mixed $value)
    {
        $this->builder->when($value === 'y', function ($query) {
            return $query->where('is_published', true);
        })->when($value === 'n', function ($query) {
            return $query->where('is_published', false);
        });
    }

    public function sort(string $sortType)
    {
        $sort = explode("|", $sortType);
        if (isset($sort) && count($sort)) {
            $this->builder->orderBy($sort[0], $sort[1] ?? 'asc');
        }
    }

    public function search(string $search)
    {
        $search = preg_replace('/[\s\+]+/', ' ', trim($search));
        $words = array_filter(explode(' ', $search));
        $this->builder->where(function (Builder $query) use ($words) {
            foreach ($words as $word) {
                $query->where('title', 'like', "%$word%");
            }
        });
    }

}
