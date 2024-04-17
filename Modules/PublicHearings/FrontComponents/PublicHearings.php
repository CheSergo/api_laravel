<?php

namespace App\Modules\PublicHearings\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

// Helpers
use App\Helpers\Api\ApiResponse;
use App\Helpers\Meta;

// Models
use App\Modules\PublicHearings\PublicHearing;

class PublicHearings extends Controller 
{
    public $model = PublicHearing::class;

    public function index() {
        $items = (object) $this->model::thisSiteFront()->published()->orderBy('published_at', 'desc')->with('sources')->with('documents')->get();

        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function show(Request $request, $slug) {
        if(!$request->slug) {
            abort(404);
        }

        $item = (object) $this->model::thisSiteFront()->published()
        ->where('slug', $request->slug)
        ->with('sources')
        ->with('documents')
        ->firstOrFail();

        if(isset($item->path)) {
            $meta = Meta::getMeta($item);
        } else {
            $meta = [];
        }

        return ApiResponse::onSuccess(200, 'success', $item, $meta);

    }
}