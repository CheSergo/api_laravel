<?php
namespace App\Modules\Meetings;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Filters\QueryFilter;

// Helpers
use Str;

class MeetingsFilter extends QueryFilter
{

    public $date;
    public $venue;

    /**
     * @param string $date
     */
    public function date(string $date)
    {
        $this->date = $date;
        $this->builder->whereDate('begin_time_at', date('Y-m-d', strtotime($date)));
    }

    /**
     * @param $venue
     * Место проведения
     */
    public function venue($venue)
    {
        $this->venue = $venue;
        if (is_numeric($venue)) {
            $this->builder->whereHas('venue', function($query) use ($venue) {
                $query->where('id', $venue);
            });
        }
        if ($venue == 'null') {
            $this->builder->doesntHave('venue');
        }
    }

}
