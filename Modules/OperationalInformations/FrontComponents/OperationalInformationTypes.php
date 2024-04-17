<?php

namespace App\Modules\OperationalInformations\FrontComponents;

use App\Http\Controllers\Controller;

// Models
use App\Modules\OperationalInformations\OperationalInformationTypes\OperationalInformationType;
use App\Modules\OperationalInformations\OperationalInformationTypes\OperationalInformationTypesFilter;

// Helpers
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

/**
 * Тип Оперативной информации
 */
class OperationalInformationTypes extends Controller 
{
    use ActionMethods;

    public $model = OperationalInformationType::class;
    public $component = 'OperationalInformation';

    public function __construct(OperationalInformationTypesFilter $filter) {
        $this->filter = $filter;
    }

    public function getTypesWithModels() {
        $item = (object) $this->model::published()
        ->orderBy('created_at', 'desc')
        ->whereHas('oper_infos', function($q) {
            $q->thisSiteFront();
        })
        ->with('media')
          ->get();
        
        return [
            'meta' => [],
            'data' => $item,
        ];
    }

}
