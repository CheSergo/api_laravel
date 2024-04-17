<?php

namespace App\Modules\Departments;

use App\Http\Controllers\Controller;

//Requests
use Illuminate\Http\Request;

// Filters
use App\Http\Filters\StandartFilter;

// Models
use App\Modules\Departments\Department;
use App\Modules\Sections\Section;
use App\Modules\Components\Component;

// Helpers
use App\Helpers\Meta;

class DepartmentFrontController extends Controller 
{
    public $filter;
    public $model = Department::class;
    public $component = 'DepartmentsList';

    public function __construct(StandartFilter $filter) {
        $this->filter = $filter;
    }

    public function index(Request $request) {

        $limit = $request->limit ? $request->limit : 10;
        $items = (object) $this->model::filter($this->filter)->where('parent_id', null)->published()
            ->orderBy('created_at', 'desc')->thisSiteFront()
            ->without('editor')->without('creator')
            ->with('workers')
            ->with('children', function($q) {
              $q->with('workers')->without('editor')->without('creator');
            })
            ->paginate($limit)->toArray();

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
        ->with('workers', function ($q) {
            $q->with('departments', function ($q2) {
                $q2->without('editor')->without('creator');
            });
        })->with('children', function($q) {
          $q->with('workers', function ($q2) {
            $q2->with('departments', function ($q3) {
                $q3->without('editor')->without('creator');
            });
          })->without('editor')->without('creator');
        })
        ->firstOrFail();

        return [
            'meta' => [
                'section' => $section,
                'breadcrumbs' => $section->breadcrumbs,
            ],
            'data' => $item,
        ];
    }

    public function departments_by_type($code) {
        $items = (object) $this->model::whereHas('type', function($q) use($code) {
            $q->where('code', $code);
        })->thisSiteFront()
        ->without('editor')->without('creator')
        ->select(['id', 'title', 'slug', 'site_id', 'is_published', 'sort', 'redirect', 'type_id'])
        ->with('workers', function ($q) {
            $q->select('id','surname','name','second_name','position','email','phone','slug','sort')
            ->with('departments', function($qe) {
                $qe->without('editor')->without('creator');
            });
        })
        ->with('front_children')
        ->first();
        $component = Component::where('parameter', $code)->firstOrFail();
        $section = Section::component($component->template)->firstOrFail();
        
        $meta = [
            'section' => [
                    'path' => $section->path
                ]
        ];

        return [
            'meta' => $meta,
            'data' => $items,
        ]; 
    }

}
