<?php

namespace App\Modules\Users\Roles;

use App\Http\Controllers\Controller;

// use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;

// Filters
use App\Http\Filters\StandartFilter;

// Models
use App\Modules\Users\Roles\Role;

// Facades
use Illuminate\Support\Facades\Validator;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

class RoleController extends Controller {
    
    use ActionMethods;

    public $filter;
    public $model = Role::class;
    public $messages = [
        'create' => 'Роль успешно добавлена',
        'edit' => 'Редактирование роли',
        'update' => 'Роль успешно изменена',
        'delete' => 'Роль успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * RoleController constructor.
     * @param StandartFilter $filter
     */
    public function __construct(StandartFilter $filter) {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index() {

        $items = (object) $this->model::filter($this->filter)->orderBy('title', 'ASC')->paginate(30)->toArray();

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

    public function list() {
        $roles = (object) $this->model::orderBy('title', 'ASC')->get();
        return [
            'meta' => [],
            'data' => $roles,
        ];
    }

    public function store(BaseRequest $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'code' => 'string|max:255|nullable',
            'guard_name' => 'string|max:255|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;
        $item->guard_name = 'sanctum';
        $item->description = $request->description;

        $item->save();

        if (isset($request->abilities) && is_array($request->abilities)) {
            $item->abilities()->attach($request->abilities);
        }


        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

    public function edit($id) {
        if($item = $this->model::with('abilities')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }
    
    public function update(BaseRequest $request, $id) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'code' => 'string|max:255|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        if($item = $this->model::find($id)) {
            $item->title = $request->title;
            $item->code = $request->code;
            $item->guard_name = 'sanctum';
            $item->description = $request->description;
    
            $item->save();

            if (isset($request->abilities) && is_array($request->abilities)) {
                $item->abilities()->sync($request->abilities);
            }
    
            return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
                'id' => $item->id,
                'title' => $item->title,
            ]);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

    }
}