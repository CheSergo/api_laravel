<?php

namespace App\Modules\Modules;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Modules\Module;
use App\Modules\Components\Component;

// Traits
use App\Traits\Actions\ActionMethods;

class ModuleController extends Controller
{
    use ActionMethods;

    public $model = Module::class;
    public $messages = [
        'create' => 'Модуль успешно добавлен',
        'edit' => 'Редактирование модуля',
        'update' => 'Модуль успешно изменен',
        'delete' => 'Модуль успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index()
    {
        $items = (object) $this->model::orderBy('title', 'ASC')
            ->with('components')
            ->paginate(30)
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

    public function list()
    {
        $items = (object) $this->model::orderBy('title', 'ASC')->get();

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

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'string|max:255|nullable',
            'description' => 'string|max:255|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;
        $item->description = $request->description;

        $item->save();

        if(isset($request->menus) && is_array($request->menus)) {
            $item->menus()->attach($request->menus);
        }

        if(isset($request->components) && is_array($request->components)) {
            // $item->components()->attach($request->components);
            // $item->components()->saveMany($request->components);
            if (count($request->components)) {
                foreach ($request->components as $component) {
                    $component = Component::find($component);
                    if($component) {
                        $item->components()->save($component);
                    }
                }
            }
        }

        if(isset($request->abilities) && is_array($request->abilities)) {
            $item->abilities()->attach($request->abilities);
        }

        if(isset($request->sites) && is_array($request->sites)) {
            $item->sites()->attach($request->sites);
        }


        // Возврат данных в ответе
        $data = [
            'id' => $item->id,
            'title' => $item->title,
            'code' => $item->code,
        ];

        return ApiResponse::onSuccess(200, $this->messages['create'], $data);
    }
    
    public function edit($id) {
        $item = $this->model::with('menus')->with('components')->with('abilities')->with('sites')->find($id);
        if($item) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'string|max:255|nullable',
            'description' => 'string|max:255|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->code = $request->code;
        $item->description = $request->description;

        $item->save();

        if(!empty($request->menus) && is_array($request->menus)) {
            $item->menus()->sync($request->menus);
        }

        if(is_array($request->components)) {
            
            $old_components = $item->components->pluck('id')->toArray();
            $new_components = $request->components;
            $components_to_del = array_diff($old_components, $new_components);
            if (count($components_to_del)) {
                foreach ($components_to_del as $id) {
                    $component = Component::find($id);
                    if($component) {
                        $component->module()->dissociate();
                        $component->saveQuietly();
                    }
                }
            }

            if (count($request->components)) {
                foreach ($request->components as $component) {
                    $component = Component::find($component);
                    if($component) {
                        $item->components()->save($component);
                    }
                }
            }
        }

        if(!empty($request->abilities) && is_array($request->abilities)) {
            $item->abilities()->sync($request->abilities);
        }

        if(!empty($request->sites) && is_array($request->sites)) {
            $item->sites()->sync($request->sites);
        }

        // Возврат данных в ответе
        $data = [
            'id' => $item->id,
            'title' => $item->title,
            'code' => $item->code,
        ];

        return ApiResponse::onSuccess(200, $this->messages['update'], $data);
    }
}
