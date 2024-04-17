<?php
namespace App\Modules\Workers;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

// Helpers
use Carbon\Carbon, Str;

// Models
use App\Models\User;

class WorkerFilter extends QueryFilter
{
    /**
     * @param string $search
     */
    public function search(string $search) {
        $search = preg_replace('/[\s\+]+/', ' ', trim($search));
        $words = array_filter(explode(' ', $search));
        foreach ($words as $word)
        {
            $this->builder->where(function (Builder $query) use ($word) {
                $query->orWhere('surname', 'like', "%$word%")
                    ->orWhere('name', 'like', "%$word%")
                    ->orWhere('second_name', 'like', "%$word%")
                    ->orWhere('position', 'like', "%$word%");
            });
        }
    }

    /**
     * @param int $department
     */
    public function department(int $department) {
        $this->builder->WhereHas('departments', function($query) use ($department) {
            $query->where('id', $department);
        });
    }

}