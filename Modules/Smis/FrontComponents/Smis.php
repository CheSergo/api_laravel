<?php

namespace App\Modules\Smis\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Modules\Smis\SmisFilter;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Smis\Smi;

class Smis extends Controller
{
    // Учреждённые СМИ
    public $filter;
    public $model = Smi::class;

    /**
     * MunicipalitiesAdminController constructor.
     * @param SmisFilter $filter
     */
    public function __construct(SmisFilter $filter) {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index() {
        $items = (object) $this->model::filter($this->filter)
            ->thisSiteFront()
            ->published()
            ->with('sources')
            ->orderBy('sort', 'asc')
            ->orderBy('title', 'asc')
            ->paginate(5)->toArray();

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