<?php

namespace App\Modules\Users\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

// Filters
use App\Http\Filters\StandartFilter;

// Requests
use App\Http\Requests\BaseRequest;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

// Modules
use App\Modules\Users\Roles\Ability;

class AbilityController extends Controller {

    use ActionMethods;

    public $filter;
    public $model = Ability::class;
    public $messages = [
        'create' => 'Разрешение успешно добавлено',
        'edit' => 'Редактирование разрешения',
        'update' => 'Разрешение успешно изменено',
        'delete' => 'Разрешение успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * AbilityController constructor.
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
        $roles = (object) $this->model::select(['id', 'title'])->orderBy('title', 'ASC')->get();
        return [
            'meta' => [],
            'data' => $roles,
        ];
    }

    public function store(BaseRequest $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'string|max:255|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

    public function edit($id) {
        if($item = $this->model::find($id)) {
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
    
            $item->save();
    
            return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
                'id' => $item->id,
                'title' => $item->title,
            ]);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

    }

}