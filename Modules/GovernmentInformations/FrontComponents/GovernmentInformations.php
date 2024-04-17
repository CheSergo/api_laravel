<?php

namespace App\Modules\GovernmentInformations\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
// use App\Modules\Admin\BaseRequest;

// Filters
use App\Modules\GovernmentInformations\GovernmentInformationsFilter;

// Models
use App\Modules\Sections\Section;
use App\Modules\GovernmentInformations\GovernmentInformation;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

class GovernmentInformations extends Controller {
    
    // Оперативная информация
    
    use ActionMethods;

    public $model = GovernmentInformation::class;
    public $component = 'GovernmentInformations';

    public function __construct(GovernmentInformationsFilter $filter) {
        $this->filter = $filter;
    }

    public function index(Request $request) {
        $builder = (object) $this->model::filter($this->filter)
        ->thisSiteFront()
        ->published()->orderBy('published_at', 'desc')
        ->withCount(['documents' => function ($query) {
            $query->published();
        }])
        ->with('sources');

        $for_meta = (object) $builder->get();
        $items = (object) $builder->filter($this->filter)->paginate(10)->toArray();

        $unique_sources = Meta::processItems($for_meta->pluck('sources')->toArray(), 'id', ['id', 'title']);
        Meta::sorting_by_title($unique_sources);

        if(isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        $meta['sources'] = $unique_sources;
        // return [
        //     'meta' => $meta,
        //     'data' => $items->data,
        // ];
        return ApiResponse::onSuccess(200, 'success', $data = $items->data, $meta);
    }

    public function show(Request $request, $id) {

        if(!$request->slug /* && !$request->sections */) {
            abort(404);
        }
        // Check sections
        // $slug_section = explode(',', $request->sections);
        
        // $section = Section::thisSiteFront()->published()->where('slug', end($slug_section))->component($this->component)->firstOrFail();
        // $section->append('breadcrumbs');
        
        $item = (object) $this->model::thisSiteFront()
        ->published()
        ->where('slug', $request->slug)
        ->with('tags')
        ->with('sources')
        ->with('documents', function($q) {
            $q->without('creator')->without('editor');
        })
        ->firstOrFail();

        $item->timestamps = false;
        $item->increment('views_count');

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
