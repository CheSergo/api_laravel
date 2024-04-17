<?php
namespace App\Http\Filters\Sites\Tarif;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Str;
use App\Models\User;
use App\Http\Filters\Common\QueryFilter;

class TarifFilter extends QueryFilter
{
    public $date;
    public $sphere;
    public $district;
    public $organization;
    public $type;

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
     * @param string $date
     */
    public function date(string $date)
    {
        $this->date = $date;
        $this->builder->whereDate('start_date', date('Y-m-d', strtotime($date)));
    }

    /**
     * @param string $sphere_tarif
     */
    public function sphere(int $sphere)
    {
        $this->sphere = $sphere;
        $this->builder->where('sphere_id', $sphere);
    }

    /**
     * @param string $district
     */
    public function district(int $district)
    {
        $this->district = $district;
        $this->builder->where('district_id', $district);
    }

    /**
     * @param string $organization
     */
    public function organization(int $organization)
    {
        $this->organization = $organization;
        $this->builder->where('organization_id', $organization);
    }

    public function type(int $type) {
        $this->type = $type;
        $this->builder->where('type_id', $type);
    }

}