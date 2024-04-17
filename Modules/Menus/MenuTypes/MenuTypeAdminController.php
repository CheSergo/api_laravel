<?php

namespace App\Modules\Menus\MenuTypes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

// Models
use App\Modules\Menus\MenuTypes\MenuType;

class MenuTypeAdminController extends Controller
{
    use ActionMethods;

    public $model = MenuType::class;
    public $messages = [
        'create' => 'Тип меню успешно добавлено',
        'edit' => 'Редактирование типа меню',
        'update' => 'Тип меню успешно изменено',
        'delete' => 'Тип меню успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index()
    {
        $items = (object) $this->model::orderBy('created_at', 'DESC')->get();
         
        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    /**
     * @return mixed
     */
    public function list()
    {
        $items = (object) $this->model::orderBy('title', 'ASC')->get();
         
        return [
            'meta' => [],
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
        $item->sort = isset($request->sort) ? $request->sort : 100;

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        if($item = $this->model::find($id)) {
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

        if($item = $this->model::find($id)) {

            $item->title = $request->title;
            $item->code = $request->code;
            $item->description = $request->description;
            $item->sort = isset($request->sort) ? $request->sort : 100;

            $item->save();
            
            return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
                'id' => $item->id,
                'title' => $item->title,
            ]);

        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

}