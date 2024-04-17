<?php
namespace App\Http\Filters\Common;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Str;
use App\Models\User;
use App\Models\Sites\Egov\ISystemsType;


class IsystemFilter extends QueryFilter
{
    /**
     * @param string $value
     */
    public function idn(string $value)
    {
        $this->builder->where('idn', 'like', "%$value%");
    }

    /**
     * @param string $value
     */
    public function title(string $value)
    {
        $this->builder->where('title', 'like', "%$value%");
    }

    /**
     * @param string $value
     */
    public function fulltitle(string $value)
    {
        $this->builder->where('fulltitle', 'like', "%$value%");
    }

    /**
     * @param string $value
     */
    public function yearCommissioned(string $value)
    {

        $this->builder->where('year_commissioned', $value);
    }

    /**
     * @param string $value
     */
    public function owner(string $value)
    {
        $this->builder->where('owner', 'like', "%$value%");
    }

    /**
     * @param int $availability
     */
    public function availability(int $availability)
    {
       $this->builder->WhereHas('types', function($query) use ($availability) {
           $query->where('isystem_type_id', $availability);

        });

    }

    /**
     * @param int $application
     */
    public function application(int $application)
    {
        $this->builder->whereHas('types', function($query) use ($application) {
            $query->where('isystem_type_id', $application);
        });
    }

    /**
     * @param string $value
     */
    public function description(string $value)
    {
        $this->builder->where('title', 'like', "%$value%");
    }

    /**
     * @param int $resource
     */
    public function resource(int $resource)
    {

        $this->builder->whereHas('resources', function($query) use ($resource) {
            $query->where('id', $resource);
        });
    }
}