<?php
namespace App\Http\Filters\Common;

use Illuminate\Database\Eloquent\Builder;
use phpDocumentor\Reflection\Types\Mixed_;
use Str, Carbon\Carbon;

class JobOpeningsFilter extends QueryFilter
{

    public $date;

    public function period(string $period) {
        $dates = explode(' - ', $period);
        if(isset($dates[0]) && isset($dates[1])) {
            $this->builder->whereBetween('begin_at', [date('Y-m-d', strtotime($dates[0])), date('Y-m-d', strtotime($dates[1]))])
            ->orWhereBetween('end_at', [date('Y-m-d', strtotime($dates[0])), date('Y-m-d', strtotime($dates[1]))]);
        }
    }

    /**
     * @param string $type
     */
    public function type(string $type)
    {
        $this->builder->where(function (Builder $query) use ($type) {
            $query->where('title', 'like', "%$type%");
        });
    }

    /**
     * @param int $source
     */
    public function source(int $source)
    {
        $this->builder->where('site_id', $source);
    }

    /**
     * @param string $status
     */
    public function status(string $status)
    {
        switch ($status) {
            case 'close':
                $this->builder->whereDate('end_at', '<', Carbon::today()->format('Y-m-d H:i:s'));
                break;
            case 'open':
                $this->builder->where('is_failed', 0)->whereDate('end_at', '>', Carbon::today()->format('Y-m-d H:i:s'));
                break;
            case 'failed':
                $this->builder->where('is_failed', 1);
                break;
        }
    }

    /**
     * @param string $date
     */
    public function date(string $date)
    {
        $this->date = $date;
        $this->builder->whereDate('published_at', date('Y-m-d', strtotime($date)));
    }

    /**
     * @param string $search
     */
    public function search(string $search)
    {
        $words = array_filter(explode(' ', $search));

        $this->builder->where(function (Builder $query) use ($words) {
            foreach ($words as $word) {
                $query->where('title', 'like', "%$word%");
            }
        });
    }

    /**
     * @param string $value
     */
    public function isPublished(string $value)
    {
        if ($value == 'yes') $active = 1 ;
        else $active = 0 ;
        $this->builder->where('is_published', $active);
    }

    /**
     * @param string $sort
     * @param string $sortType
     */
    public function sort(string $sort, $sortType = 'asc')
    {
        $this->builder->orderBy($sort, $sortType);
    }
}
