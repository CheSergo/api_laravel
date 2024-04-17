<?php
namespace App\Modules\Documents\DocumentTypes;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

// Helpers
use Carbon\Carbon, Str;

// Models
use App\Models\User;

class DocumentTypesFilter extends QueryFilter
{
    public $date;

    public function isMpa(int $bool) {
        $this->builder->where('is_mpa', $bool);
    }

    public function isStatus(int $bool) {
        $this->builder->where('is_status', $bool);
    }

    public function isAnticorruption(int $bool) {
        $this->builder->where('is_anticorruption', $bool);
    }

    public function isAntimonopoly(int $bool) {
        $this->builder->where('is_antimonopoly', $bool);
    }
    
}
