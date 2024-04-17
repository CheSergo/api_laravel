<?php
namespace App\Modules\Logs;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
// use App\Http\Filters\QueryFilter;

class LogFilter // extends QueryFilter
{
    function makePlural($word) {
        $lastChar = strtolower($word[strlen($word) - 1]);
        $secondLastChar = strtolower($word[strlen($word) - 2]);
    
        if ($lastChar === 'y') {
            return substr($word, 0, -1) . 'ies';
        } elseif ($lastChar === 's' || $lastChar === 'x' || $lastChar === 'z' || $secondLastChar === 'c' || $secondLastChar === 's') {
            return $word . 'es';
        } else {
            return $word . 's';
        }
    }

    function createFullyQualifiedClassName($className) {
        $baseNamespace = 'App\Modules';
        $moduleNamePattern = ucfirst(strtolower($className));
        $fullyQualifiedClassName = $baseNamespace . '\\' . $this->makePlural($className) . '\\' . $className;
        return $fullyQualifiedClassName;
    }

    public function model($builder, $model) {
        if (!is_null($model)) {
            return $builder->where('subject_type', $this->createFullyQualifiedClassName($model));
        } else {
            return $builder;
        }
    }

    public function user($builder, $user_id) {
        if (!is_null($user_id)) {
            return $builder->where('causer_id', $user_id);
        } else {
            return $builder;
        }
    }

    public function title($builder, $title, $model) {
        if (!is_null($title) && !is_null($model)) {
            $model = $this->createFullyQualifiedClassName($model);
            $items = $model::where('title', 'LIKE', '%'.$title.'%')->get()->pluck('id');
            return $builder->whereIn('subject_id', $items);
        } else {
            return $builder;
        }
    }

    public function date($builder, $value) {
        if (!is_null($value)) {
            // $date = Carbon::createFromTimestamp($timestamp)->format('Y-m-d');
            $date = Carbon::parse($value)->format('Y-m-d');
            return $builder->whereDate('created_at', $date);
        } else {
            return $builder;
        }

    }
}