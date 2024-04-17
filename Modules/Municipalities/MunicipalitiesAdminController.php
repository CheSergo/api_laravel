<?php

namespace App\Modules\Municipalities;

use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Validator;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Filters
use App\Modules\Municipalities\MunicipalitieFilter;

// Models
use App\Http\Controllers\Controller;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class MunicipalitiesAdminController extends Controller {

    use ActionMethods, ActionsSaveEditItem;

    // Муниципальные образования
    public $filter;
    public $model = 'App\Modules\Municipalities\Municipalitie';
    public $messages = [
        'create' => 'Муниципальное образование успешно добавлено',
        'edit' => 'Редактирование муниципального образования',
        'update' => 'Муниципальное образование успешно изменено',
        'delete' => 'Муниципальное образование успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * MunicipalitiesAdminController constructor.
     * @param MunicipalitieFilter $filter
     */
    public function __construct(MunicipalitieFilter $filter) {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index() {
        $items = (object) $this->model::filter($this->filter)
            ->thisSite(false)
            ->orderBy('created_at', 'desc')
            ->with('creator')
            ->with('editor')
            ->paginate(10)->toArray();

        if (isset($items->path)) {
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
            'title' => 'required|string|max:500',
            'site' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->site = $request->site;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->site_id = request()->user()->active_site_id;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;

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
        if ($item = $this->model::find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }
    }

    public function update(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:500',
            'site' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);

        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->site = $request->site;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}

