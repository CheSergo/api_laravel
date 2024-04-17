<?php

namespace App\Modules\Sections\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Sections\Section;

class Sections extends Controller
{
    public $model = Section::class;

    public function getSection(Request $request) {
        if(!$request->sections) {
            abort(404);
        }
        
        // Check sections
        $slug_section = explode(',', $request->sections);
        $section = Section::thisSiteFront()->published()->where('slug', end($slug_section))->firstOrFail();
        $section->append('breadcrumbs');

        return ApiResponse::onSuccess(200, '', (object) $section);
    }

    public function show(Request $request) {
        $item = $this->model::thisSiteFront()->published()->where('slug', $request->slug)
            ->with('subitems:id,title,slug,redirect,parent_id,path')
            ->with('parent', function($query) {
                $query->with('subitems', function($query) {
                    $query->published();
                });
            })->with('documents', function($query) {
                $query->published();
            })->with('components')->with('media')->firstOrFail();

        return ApiResponse::onSuccess(200, '', (object) $item->append('breadcrumbs'));
    }

    public function menu() {
        $items = (object) $this->model::thisSiteFront()
            ->published()->isShow()->where('parent_id', null)
            ->select('id', 'title', 'slug', 'redirect', 'parent_id', 'path')
            ->with('subitems:id,title,slug,redirect,parent_id,path')
            ->orderBy('sort', 'ASC')->get();

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

}