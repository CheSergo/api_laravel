<?php
namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class QueryFilterBase
 * @package App\Http\Filters
 */
abstract class QueryFilterBase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
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
      
        return array_filter($this->request->all());
    }
}