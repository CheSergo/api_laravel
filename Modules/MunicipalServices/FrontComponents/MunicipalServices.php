<?php

namespace App\Modules\MunicipalServices\FrontComponents;

use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Validator;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Models
use App\Http\Controllers\Controller;

class MunicipalServices extends Controller {

    public $filter;
    public $model = 'App\Modules\MunicipalServices\MunicipalService';

    // public function __construct(MunicipalitieFilter $filter) {
    //     $this->filter = $filter;
    // }

    public function index() {
        $items = (object) $this->model::thisSiteFront()
            ->published()
            ->orderBy('title', 'asc')
            ->orderBy('sort', 'asc')
            ->with('documents', function($q) {
                $q->without('creator')->without('editor');
            })
            ->get();

        if (isset($items->path)) {
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