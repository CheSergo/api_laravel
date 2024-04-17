<?php

namespace App\Modules\MunicipalServices;

use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Validator;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Filters
use App\Modules\MunicipalServices\MunicipalServicesFilter;

// Models
use App\Http\Controllers\Controller;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class MunicipalServicesAdminController extends Controller {
    use ActionMethods, ActionsSaveEditItem;

    // Муниципальные образования
    public $filter;
    public $model = 'App\Modules\MunicipalServices\MunicipalService';
    public $messages = [
        'create' => 'Реестр муниципальных услуг успешно добавлен',
        'edit' => 'Редактирование реестра муниципальных услуг',
        'update' => 'Реестр муниципальных услуг успешно изменен',
        'delete' => 'Реестр муниципальных услуг успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * MunicipalitiesAdminController constructor.
     * @param MunicipalServicesFilter $filter
     */
     public function __construct(MunicipalServicesFilter $filter) {
         $this->filter = $filter;
     }

    /**
     * @return mixed
     */
    public function index() {
        $items = (object) $this->model::filter($this->filter)->thisSite()
            ->orderBy('created_at', 'desc')
            ->with('creator')
            ->with('editor')
            ->with('documents')
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
            'link' => 'string|max:500|nullable',
            'description' => 'string|max:500|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->link = $request->link;
        $item->description = $request->description;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->site_id = request()->user()->active_site_id;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Прикрепленеие документов
        if(isset($request->documents) && count($request->documents)) {
            $item->documents()->attach($request->documents);
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = $item);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function edit($id) {
        $item = $this->model::with('documents')->with('editor')->with('creator')->find($id);
        if ($item) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }
    }

    public function update(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:500',
            'link' => 'string|max:500|nullable',
            'description' => 'string|max:500|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);

        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->link = $request->link;
        $item->description = $request->description;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->site_id = request()->user()->active_site_id;

        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Обновление документов
        if($request->documents && is_array($request->documents)) {
            $item->documents()->detach();
            if(count($request->documents)) {
                foreach($request->documents as $index => $document) {
                    $item->documents()->attach($document, ['document_sort' => $index+1]);
                }
            }
        }

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);
    }
}