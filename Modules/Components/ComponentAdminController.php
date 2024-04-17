<?php
namespace App\Modules\Components;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

// Requests
use App\Http\Requests\BaseRequest;

// Filters
use App\Modules\Components\ComponentsFilter;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Components\Component;
use App\Modules\Modules\Module;

// Traits
use App\Traits\Actions\ActionMethods;

class ComponentAdminController extends Controller 
{
    use ActionMethods;

    public $filter;
    public $model = Component::class;
    public $messages = [
        'create' => 'Компонент успешно добавлен',
        'edit' => 'Редактирование компонента',
        'update' => 'Компонент успешно изменен',
        'delete' => 'Компонент успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    public function index(ComponentsFilter $filter)
    {
        $items = (object) $this->model::filter($filter)->with('module')->orderBy('title', 'ASC')->get();
        
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

    public function admin_list() 
    {
        $items = (object) $this->model::orderBy('title', 'ASC')->select('id', 'title', 'description')->get();

        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function list() {
        $active_site_id = request()->user()->active_site_id;
        $modules = Module::whereHas('sites', function($q) use ($active_site_id) {
            $q->whereIn('id', [$active_site_id]);
        })->with('components:id,title,description,template,is_allow_duplication,module_id')->orderBy('title')->get()->pluck('components');

        $components = [];
        foreach($modules as $module) {
            if(count($module)) {
                foreach($module as $component) {
                    array_push($components, $component);
                }
            }
        }

        $titles = array();
        foreach($components as $key => $component) {
            $titles[$key] = $component->title;
        }
        array_multisort($titles, $components);
        return [
            'meta' => [],
            'data' => $components,
        ];
    }

    public function store(BaseRequest $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'string|max:500|nullable',
            'template' => 'string|max:255|nullable',
            'controller' => 'string|max:255|nullable',
            'parameter' => 'string|max:255|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->template = $request->template;

        $item->description = $request->description;
        $item->controller = $request->controller;
        $item->parameter = $request->parameter;

        $item->module_id = $request->module_id;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->is_complex = !empty($request->is_complex) ? 1 : 0;

        $item->save();

        // if(isset($request->modules) && count($request->modules)) {
            // $item->modules()->attach($request->modules);
        // }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

    public function edit($id) {
        if($item = $this->model::with('module')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(BaseRequest $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'string|max:500|nullable',
            'template' => 'string|max:255|nullable',
            'controller' => 'string|max:255|nullable',
            'parameter' => 'string|max:255|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->template = $request->template;

        $item->description = $request->description;
        $item->controller = $request->controller;
        $item->parameter = $request->parameter;
        
        $item->module_id = $request->module_id;

        $item->editor_id = request()->user()->id;

        $item->is_complex = !empty($request->is_complex) ? 1 : 0;

        $item->save();

        // $item->modules()->sync($request->modules);

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}
