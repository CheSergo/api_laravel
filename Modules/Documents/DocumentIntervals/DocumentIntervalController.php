<?php

namespace App\Modules\Documents\DocumentIntervals;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseRequest;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Documents\DocumentIntervals\DocumentInterval;

// Traits
use App\Traits\Actions\ActionMethods;

/**
 * Список временных интервалов для отчетов
 * Интервалы отчетов документов
 */
class DocumentIntervalController extends Controller 
{
    use ActionMethods;

    public $model = DocumentInterval::class;
    public $messages = [
        'create' => 'Интервал успешно добавлен',
        'edit' => 'Редактирование интервала',
        'update' => 'Интервал успешно изменен',
        'delete' => 'Интервал успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    public function index() {
        $items = (object) $this->model::orderBy('sort', 'desc')
        ->with('creator:id,name,surname,second_name,email,phone,position')
        ->with('editor:id,name,surname,second_name,email,phone,position')
        ->paginate(10)->toArray();

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
        return $this->model::orderBy('sort', 'DESC')->get();

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

    public function store(BaseRequest $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:255',
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

        // $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

    public function edit($id) {
        if($item = $this->model::with('creator:id,name,surname,second_name,email,phone,position')
        ->with('editor:id,name,surname,second_name,email,phone,position')
        ->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(BaseRequest $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:255',
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
        
        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        // $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}
