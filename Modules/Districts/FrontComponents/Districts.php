<?php

namespace App\Modules\Districts\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

// Helpers
use App\Helpers\Api\ApiResponse;
use App\Helpers\Meta;

// Models
use App\Modules\Districts\District;

class Districts extends Controller 
{
    public $model = District::class;

    public function index() {
        $items = (object) $this->model::published()->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->with('media')->get();

        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function show(Request $request, $code) {
        if(!$request->code) {
            abort(404);
        }

        $item = (object) $this->model::published()
        ->where('code', $request->code)
        ->with('media')
        ->firstOrFail();

        if(isset($item->path)) {
            $meta = Meta::getMeta($item);
        } else {
            $meta = [];
        }

        return ApiResponse::onSuccess(200, 'success', $data = $item, $meta);

    }
}