<?php

namespace App\Modules\OperationalInformations\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Modules\Admin\BaseRequest;

// Filters
use App\Modules\OperationalInformations\OperationalInformationsFilter;

// Models
use App\Modules\Sections\Section;
use App\Modules\OperationalInformations\OperationalInformation;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

class OperationalInformations extends Controller {
    
    // Оперативная информация
    
    use ActionMethods;

    public $model = OperationalInformation::class;
    public $component = 'OperationalInformation';

    public function __construct(OperationalInformationsFilter $filter) {
        $this->filter = $filter;
    }

    public function index(Request $request) {
        $items = (object) $this->model::filter($this->filter)
        ->thisSiteFront()
        ->published()->orderBy('created_at', 'desc')
        ->withCount(['documents' => function ($query) {
            $query->published();
        }])
        ->paginate(10)->toArray();

        if(isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }
        
        return ApiResponse::onSuccess(200, 'success', $data = $items->data, $meta);
    }

    public function show(Request $request, $id) {

        // if(!$request->sections) {
        //     abort(404);
        // }
        // // Check sections
        // $slug_section = explode(',', $request->sections);

        // $section = Section::thisSiteFront()->published()->where('slug', end($slug_section))->component($this->component)->firstOrFail();
        // $section->append('breadcrumbs');

        $item = (object) $this->model::thisSiteFront()
        ->published()
        ->with('type')
        ->with('tags')
        ->with('sources')
        ->with('documents', function($q) {
            $q->without('creator')->without('editor');
        })
        ->find($id);

        return [
            'meta' => [
                // 'section' => $section,
                // 'breadcrumbs' => $section->breadcrumbs,
            ],
            'data' => $item,
        ];


//        return ApiResponse::onSuccess(200, 'success', $data = $item, $meta);
    }
}
