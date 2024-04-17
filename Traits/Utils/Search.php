<?php

namespace App\Traits\Utils;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;

trait Search {

    // protected function escape_like($value)
    // {
    //     return str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $value);
    // }

    // public function scopeSearch($query, string $key = null, $fields = null)
    // {
    //     if (!$key || !$fields) {
    //         return $query;
    //     }
    //     $fields = (array) $fields;

    //     $words = explode(' ', $key);

    //     $iter = 1;
    //     foreach ($fields as $field) {
    //         if ($iter == 1) {
    //             $query = $query->where($field, 'like', '%' . $this->escape_like($key) . '%');
    //         } else {
    //             $query = $query->orWhere($field, 'like', '%' . $this->escape_like($key) . '%');
    //         }
    //         foreach ($words as $word) {
    //             $query->orWhere($field, 'like', "%{$word}%");
    //         }
    //         $iter = 0;
    //     }

    //     return $query;
    // }
    protected function scopeSearch($query)
    {
        [$searchTerm, $attributes, $unicodes] = $this->parseArguments(func_get_args());

        if (!$searchTerm || !$attributes) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($attributes, $searchTerm, $unicodes) {
            foreach (Arr::wrap($attributes) as $attribute) {
                $query->when(
                    str_contains($attribute, '.'),
                    function (Builder $query) use ($attribute, $searchTerm, $unicodes) {
                        [$relationName, $relationAttribute] = explode('.', $attribute);

                        $query->orWhereHas($relationName, function (Builder $query) use ($relationAttribute, $searchTerm, $unicodes) {
                            if (!is_null($unicodes) && in_array($relationAttribute, $unicodes)) {
                                $uncodedSearchTerm = json_encode($searchTerm);
                                $uncodedSearchTerm = str_replace(["'", '"'], '', $uncodedSearchTerm);
                                $uncodedSearchTerm = str_replace("\\", "\\\\", $uncodedSearchTerm);
                                if ($relationAttribute == 'body') {
                                    $pattern = '"body": { "blocks": [ {
                                        "data": {
                                            "text": '.$uncodedSearchTerm.'
                                        },';
                                } else {
                                    $pattern = $uncodedSearchTerm;
                                }
                                $query->orWhere($relationAttribute, 'LIKE', "%{$pattern}%");
                            }
                            if ($relationAttribute == 'body') {
                                $pattern = '"body": { "blocks": [ {
                                    "data": {
                                        "text": '.$searchTerm.'
                                    },';
                            } else {
                                $pattern = $searchTerm;
                            }
                            $query->where($relationAttribute, 'LIKE', "%{$pattern}%");
                        });
                    },
                    function (Builder $query) use ($attribute, $searchTerm, $unicodes) {
                        if (!is_null($unicodes) && in_array($attribute, $unicodes)) {
                            $uncodedSearchTerm = json_encode($searchTerm);
                            $uncodedSearchTerm = str_replace(["'", '"'], '', $uncodedSearchTerm);
                            $uncodedSearchTerm = str_replace("\\", "\\\\", $uncodedSearchTerm);
                            if ($attribute == 'body') {
                                $pattern = '"body": { "blocks": [ {
                                    "data": {
                                        "text": '.$uncodedSearchTerm.'
                                    },';
                            } else {
                                $pattern = $uncodedSearchTerm;
                            }
                            $query->orWhere($attribute, 'LIKE', "%{$pattern}%");
                        }
                        if ($attribute == 'body') {
                            $pattern = '"body": { "blocks": [ {
                                "data": {
                                    "text": '.$searchTerm.'
                                },';
                        } else {
                            $pattern = $searchTerm;
                        }
                        $query->orWhere($attribute, 'LIKE', "%{$pattern}%");
                    }
                );
            }
        });
    }

    /**
     * Parse search scope arguments
     *
     * @param array $arguments
     * @return array
     */
    private function parseArguments(array $arguments)
    {
        $args_count = count($arguments);

        switch ($args_count) {
            case 1:
                return [request(config('searchable.key')), $this->searchableAttributes()];
                break;

            case 2:
                return is_string($arguments[1])
                    ? [trim($arguments[1]), $this->searchableAttributes(), null]
                    : [request(config('searchable.key')), trim($arguments[1]), null];
                break;

            case 3:
                return is_string($arguments[1])
                    ? [trim($arguments[1]), $arguments[2], null]
                    : [$arguments[2], trim($arguments[1]), null];
                break;

            case 4:
                return is_string($arguments[1])
                    ? [trim($arguments[1]), $arguments[2], $arguments[3]]
                    : [$arguments[2], trim($arguments[1]), $arguments[3]];
                break;

            default:
                return [null, [], []];
                break;
        }
    }

    /**
     * Get searchable columns
     *
     * @return array
     */
    public function searchableAttributes()
    {
        if (method_exists($this, 'searchable')) {
            return $this->searchable();
        }

        return property_exists($this, 'searchable') ? $this->searchable : [];
    }
}