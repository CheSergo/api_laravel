<?php

namespace App\Modules\Institutions\InstitutionTypes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\BaseRequest;

// Traits
use App\Traits\Actions\ActionMethods;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Institutions\InstitutionTypes\InstitutionType;

class InstitutionTypeAdminController extends Controller
{
    use ActionMethods;

    public $model = InstitutionType::class;
    public $messages = [
        'create' => 'Тип подведомственного учреждения успешно добавлено',
        'edit' => 'Редактирование типа подведомственного учреждения',
        'update' => 'Тип подведомственного учреждения успешно изменен',
        'delete' => 'Тип подведомственного учреждения успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index()
    {
        $items = (object) $this->model::orderBy('title', 'ASC')->orderBy('sort', 'ASC')
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

    public function list()
    {
        $items = (object) $this->model::orderBy('title', 'ASC')->orderBy('sort', 'ASC')->get();

        if (isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        return [
            'meta' => $meta,
            'data' => $items,
        ];
    }

    public function store(BaseRequest $request)
    {

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:500',
            'code' => 'required|max:500',
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

        $item->is_published = !is_null($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = $item);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        if ($item = $this->model::find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(BaseRequest $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:500',
            'code' => 'required|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);

        if (!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->code = $request->code;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->editor_id = request()->user()->id;

        $item->is_published = !is_null($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);
    }
}
