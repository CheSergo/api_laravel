<?php

namespace App\Modules\Meetings\MeetingTypes;

use App\Http\Controllers\Controller;

// Requests
use Illuminate\Http\Request;

// Models
use App\Modules\Meetings\MeetingTypes\MeetingType;

// Facades
use Illuminate\Support\Facades\Validator;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;
use Carbon\Carbon;
use Str;

// Traits
use App\Traits\Actions\ActionMethods;

/**
 * Типы заседаний
 */
class MeetingTypeAdminController extends Controller
{
    use ActionMethods;

    public $model = MeetingType::class;
    public $messages = [
        'create' => 'Тип заседания успешно добавлен',
        'edit' => 'Редактирование типа заседания',
        'update' => 'Тип заседания успешно изменен',
        'delete' => 'Тип заседания успешно удален',
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
        $items = (object) $this->model::orderBy('title', 'ASC')->published()->get();

        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function store(Request $request) {

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
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->creator_id = $request->user()->id;
        $item->editor_id = $request->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

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
    public function edit($id) {
        if($item = $this->model::find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(Request $request, $id) {

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->code = $request->code;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->editor_id = $request->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);

    }
}
