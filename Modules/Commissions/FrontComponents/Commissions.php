<?php

namespace App\Modules\Commissions\FrontComponents;

use App\Http\Controllers\Controller;

// Requests
use Illuminate\Http\Request;

// Helpers
use App\Helpers\Meta;

//Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Models
use App\Modules\Commissions\Commission;
use App\Modules\Sections\Section;

class Commissions extends Controller {

    use ActionMethods, ActionsSaveEditItem;
    
    public $model = Commission::class;
    public $component = 'Commissions';

    public function index()
    {
        $items = (object) $this->model::thisSiteFront()
        ->select(['id', 'title', 'slug', 'body', 'redirect'])
        ->withCount('documents')
        ->withCount('heads')
        ->withCount('members')
        ->without('editor')->without('creator')
        ->orderBy('title')
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

    public function show($slug) {
        if(!$slug) {
            abort(404);
        }

        // Check sections
        $item = $this->model::thisSiteFront()->published()->where('slug', $slug)
        ->with('heads')->with('members')->with('documents')
        ->without('editor')->without('creator')
        ->firstOrFail();
        // $slug_section = explode(',', $request->sections);
        
        // $section = Section::thisSiteFront()->published()->where('slug', end($slug_section))->whereHas('components', function($query) {
        //     $query->where('template', $this->component);
        // })->firstOrFail();
        // $section->append('breadcrumbs');
        
        // $item = (object) $this->model::thisSiteFront()
        // ->published()
        // ->where('slug', $request->slug)
        // // ->with('directions')
        // // ->with('departments')
        // // ->with('meetings')
        // ->with('heads')
        // ->with('members')
        // ->firstOrFail();

        return [
            'meta' => [
                // 'section' => $section,
                // 'breadcrumbs' => $section->breadcrumbs,
            ],
            'data' => $item,
        ];
    }

}
