<?php

namespace App\Modules\Links\LinkTypes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;
// use App\Modules\Admin\BaseRequest;

// Models
use App\Modules\Links\LinkTypes\LinkType;
use App\Modules\Sites\Site;
// Helpers
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

class LinkTypeAdminController extends Controller
{
    use ActionMethods;

    public $model = LinkType::class;
    public $messages = [
        'create' => 'Тип ссылки успешно добавлен',
        'edit' => 'Редактирование типа ссылки',
        'update' => 'Тип ссылки успешно изменен',
        'delete' => 'Тип ссылки успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index() {

        $items = (object) $this->model::with('creator')->with('editor')
        ->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->get();
         
        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    /**
     * @return mixed
     */
    public function list() {

        $items = (object) $this->model::select(['id', 'title', 'code', 'description'])
        ->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->get();
         
        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function list_this_site() {
        $site = Site::findOrFail(request()->user()->active_site_id);
        return [
            'meta' => [],
            'data' => $site->link_types->map->only('id', 'title'),
        ]; 
    }

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'string|max:255',
            'description' => 'string|max:500|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;
        $item->description = $request->description;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

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
        if ($item = $this->model::with('creator')->with('editor')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'string|max:255',
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
        $item->code = $request->code;//HString::transliterate($request->title);
        $item->description = $request->description;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->update();

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }
}