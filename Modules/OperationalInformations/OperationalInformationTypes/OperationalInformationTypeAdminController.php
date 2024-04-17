<?php

namespace App\Modules\OperationalInformations\OperationalInformationTypes;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Modules\Admin\BaseRequest;

// Models
use App\Modules\OperationalInformations\OperationalInformationTypes\OperationalInformationType;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

class OperationalInformationTypeAdminController extends Controller {

    // Тип Оперативной информации
    
    use ActionMethods;

    public $model = OperationalInformationType::class;
    public $messages = [
        'create' => 'Тип Оперативной информации успешно добавлен',
        'edit' => 'Редактирование Типа Оперативной информации',
        'update' => 'Тип Оперативной информации успешно изменен',
        'delete' => 'Тип Оперативной информации успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    public function index() {

        $items = (object) $this->model::orderBy('created_at', 'DESC')->paginate(10)->toArray();

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

        $items = (object) $this->model::select('id', 'title')->orderBy('title', 'ASC')->orderBy('sort', 'ASC')->get();

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

    public function store(Request $request) {
        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        
        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        if(isset($request->poster) && count($request->poster)) {
            HRequest::save_poster($item, $request->poster, 'operinfo_icons');
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $item);
    }

    public function edit($id) {
        $item = $this->model::with('creator')->with('editor')->with('media')->find($id);

        if($item) {
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
        
        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        if ($request->poster && count($request->poster)) {
            if(!$request->poster['id']) {
                $item->clearMediaCollection('operinfo_icons');
            }
            HRequest::save_poster($item, $request->poster, 'operinfo_icons');
        } else {
            $item->clearMediaCollection('operinfo_icons');
        } 

        return ApiResponse::onSuccess(200, $this->messages['create'], $item);
    }

}