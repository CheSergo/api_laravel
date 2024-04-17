<?php
namespace App\Modules\Departments\FrontComponents;

use App\Http\Controllers\Controller;

use App\Helpers\Meta;

// Traits
use App\Traits\Actions\ActionMethods;

// Models
use App\Modules\Departments\DepartmentTypes\DepartmentType;

class DepartmentTypes extends Controller 
{
    use ActionMethods;
    
    public $model = DepartmentType::class;

    public function departments_by_type($code) {
        $items = (object) $this->model::where('code', $code)->with('departments.workers')->first();

        if(isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        return [
            'meta' => $meta,
            'data' => $items,
        ]; 
    }
}