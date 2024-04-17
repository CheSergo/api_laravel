<?php

namespace App\Modules\Districts;

use App\Http\Controllers\Controller;

use App\Http\Requests\BaseRequest;

// Filters
use App\Modules\Districts\DistrictsFilter;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

// Models
use App\Modules\Districts\District;

class DistrictAdminController extends Controller 
{
    use ActionMethods;
    
    public $model = District::class;
    public $messages = [
        'create' => 'Район успешно добавлен',
        'edit' => 'Редактирование района',
        'update' => 'Район успешно изменен',
        'delete' => 'Район успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index(DistrictsFilter $filter) {
        $items = (object) $this->model::filter($filter)->orderBy('title', 'asc')->paginate(20)->toArray();
        
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
        $items = (object) $this->model::orderBy('sort', 'ASC')->orderBy('title', 'ASC')->get();

        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function store(BaseRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;

        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->site = $request->site;
        $item->adm_center = $request->adm_center;
        $item->location = $request->location;
        $item->population = $request->population;
        $item->square = $request->square;
        $item->body = $request->body;
        
        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        // Приклепление медиа
        if(isset($request->poster) && count($request->poster)) {
            HRequest::save_poster($item, $request->poster, 'district_posters');
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

    public function edit($id) {
        if($item = $this->model::with('media')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(BaseRequest $request, $id) {

        $item = $this->model::find($id);

        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->code = $request->code;

        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->site = $request->site;
        $item->adm_center = $request->adm_center;
        $item->location = $request->location;
        $item->population = $request->population;
        $item->square = $request->square;
        $item->body = $request->body;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        HRequest::save_poster($item, $request->poster, "district_posters");

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }


}