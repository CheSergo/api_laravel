<?php

namespace App\Modules\InformationSystems\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Modules\Admin\BaseRequest;

// Filters
use App\Modules\InformationSystems\InformationSystemsFilter;

// Models
use App\Modules\Sections\Section;
use App\Modules\InformationSystems\InformationSystem;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
// use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

class InformationSystems extends Controller {
    
    // Информационные системы
    
    use ActionMethods;

    public $model = InformationSystem::class;
    public $component = 'InformationSystems';

    public function __construct(InformationSystemsFilter $filter) {
        $this->filter = $filter;
    }

    public function index() {
        $items = (object) $this->model::filter($this->filter)
        ->thisSiteFront()->published()
        ->orderBy('created_at', 'desc')
        ->with('owner', function($q) {
            $q->select('id', 'title', 'slug');
        })
        ->paginate(10)->toArray();

        if(isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }
        
        return ApiResponse::onSuccess(200, 'success', $data = $items->data, $meta);
    }

    public function show(Request $request, $slug) {

        // if(!$request->sections) {
        //     abort(404);
        // }
        // Check sections
        // $slug_section = explode(',', $request->sections);

        // $section = Section::thisSiteFront()->published()
        //     ->where('slug', end($slug_section))
        //     ->component($this->component)
        //     ->firstOrFail();
        // $section->append('breadcrumbs');

        $item = (object) $this->model::thisSiteFront()
            ->where('slug', $slug)
            ->published()
            ->with('owner', function($q) {
                $q->select('id', 'title', 'slug');
            })
            ->with('information_systems', function($q) {
                $q->select('id', 'title', 'slug');
            })
            ->with('documents', function($q) {
                $q->without('creator')->without('editor');
            })
            ->firstOrFail();

        $meta = [
            // 'breadcrumbs' => $section->breadcrumbs,
        ];

        return ApiResponse::onSuccess(200, 'success', $data = $item, $meta);
    }
}