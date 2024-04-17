<?php
namespace App\Modules\Workers\FrontComponents;

use App\Http\Controllers\Controller;

// Filters
use App\Modules\Workers\WorkerFilter;

// Requests
use Illuminate\Http\Request;
use App\Modules\Workers\WorkerRequest;

// Helpers
// use Carbon\Carbon;
use App\Helpers\Meta;
// use App\Helpers\HRequest;
// use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Workers\Worker;
use App\Modules\Sections\Section;
use App\Modules\Components\Component;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class Workers extends Controller 
{
    use ActionMethods, ActionsSaveEditItem;
    
    public $model = Worker::class;

    /**
     * @return mixed
     */
    public function index(Request $request)
    {
        $items = (object) $this->model::thisSite()->orderBy('published_at', 'desc')
        ->with('user')
        ->with('site')
        // ->with('media')
        ->with('departments')
        ->paginate(10)
        ->toArray();

        if(isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        return [
            'meta' => $meta,
            'data' => $items->data,
        ];
    }

    /**
     * @return mixed
     */
    public function list(WorkerFilter $filter) {
        $items = (object) $this->model::published()->filter($filter)/*->with('media')*/->get();
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

    public function show(Request $request) {
        if(!$request->sections || !$request->slug) {
            abort(404);
        }

        // Check sections
        $slug_section = explode(',', $request->sections);
        $section = Section::thisSiteFront()->published()->where('slug', end($slug_section))->firstOrFail();
        $section->append('breadcrumbs');

        $ids = [];
        foreach($section->body['blocks'] as $block) {
            if($block['type'] == 'components') {
                array_push($ids, $block['data']['id']);
            }
        }
        $components_parameters = Component::whereIn('id', $ids)->get()->pluck('parameter')->toArray();
        if(!in_array($request->type, $components_parameters)) {
            abort(404);
        }

        $item = (object) $this->model::published()->where('slug', $request->slug)
        ->without('editor')->without('creator')
        ->with('departments')
        ->with('documents')
        ->firstOrFail();

        return [
            'meta' => [
                'section' => $section,
                'breadcrumbs' => $section->breadcrumbs,
            ],
            'data' => $item,
        ];
    }

}