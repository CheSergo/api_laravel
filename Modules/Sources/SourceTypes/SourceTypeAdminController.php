<?php

namespace App\Modules\Sources\SourceTypes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

// Requests
use Illuminate\Http\Request;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

//Models
use App\Modules\Sources\SourceTypes\SourceType;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class SourceTypeAdminController extends Controller 
{
    use ActionsSaveEditItem, ActionMethods;

    public $model = SourceType::class;
    public $messages = [
        'create' => 'Тип Источника успешно добавлен',
        'edit' => 'Редактирование Типа Источника',
        'update' => 'Тип Источника успешно изменен',
        'delete' => 'Тип Источника успешно удален',
        'not_found' => 'Элемент не найден',
        'exist' => 'Тип Источника с таким именем уже существует',
    ];

    /**
     * @return mixed
     */
    public function index()
    {
        $items = (object) $this->model::orderBy('created_at', 'DESC')
            ->paginate(18)->toArray();

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

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:500',
            'code' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['create'], $item);
    }

    public function list() {

        $items = (object) $this->model::select('id', 'title')->orderBy('created_at', 'DESC')->get();

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

    public function edit($id) {
        $item = $this->model::with('creator')->with('editor')->find($id);

        if($item) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:500',
            'code' => 'string|max:255',
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
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['update'], $item);
    }
}