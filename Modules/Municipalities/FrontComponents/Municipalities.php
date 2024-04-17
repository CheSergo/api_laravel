<?php

namespace App\Modules\Municipalities\FrontComponents;

use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;

// Helpers
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Filters
use App\Modules\Municipalities\MunicipalitieFilter;

// Models
use App\Http\Controllers\Controller;
use App\Modules\Municipalities\Municipalitie;

class Municipalities extends Controller {

    // Муниципальные образования
    public $filter;
    public $model = Municipalitie::class;

    /**
     * MunicipalitiesAdminController constructor.
     * @param MunicipalitieFilter $filter
     */
    public function __construct(MunicipalitieFilter $filter) {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index() {
        $items = (object) $this->model::filter($this->filter)
            ->thisSiteFront()
            ->orderBy('sort', 'asc')
            ->orderBy('title', 'asc')
            ->paginate(10)->toArray();

        if (isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        return [
            'meta' => $meta,
            'data' => $items->data,
        ];
    }

}

