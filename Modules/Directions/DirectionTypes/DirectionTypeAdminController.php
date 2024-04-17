<?php

namespace App\Modules\Directions\DirectionTypes;

use App\Http\Controllers\Controller;

use App\Modules\Directions\DirectionRequest;

// Filters
use App\Modules\Directions\DirectionsFilter;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Directions\DirectionTypes\DirectionType;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class DirectionTypeAdminController extends Controller 
{
    use ActionMethods, ActionsSaveEditItem;
    
    public $model = DirectionType::class;
    public $messages = [
        'create' => 'Вид направления деятельности успешно добавлено',
        'edit' => 'Редактирование Вида направления деятельности',
        'update' => 'Вид направления деятельности успешно изменено',
        'delete' => 'Вид направления деятельности успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index(DirectionsFilter $filter)
    {
        $items = (object) $this->model::filter($filter)
            ->container()
            ->with('media')
            ->with('childs')
            ->with('creator:id,name,surname,second_name,email,phone,position')
            ->with('editor:id,name,surname,second_name,email,phone,position')
            ->orderBy('title', 'ASC')->get();

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

    /**
     * @return mixed
     */
    public function list() {

        $items = (object) $this->model::select('id', 'title')->orderBy('title', 'ASC')->orderBy('sort', 'ASC')->with('children:id,parent_id,title')->get();
        // $items = (object) $this->model::whereHas('directions', function($q) {
            // $q->thisSite()->published();
        // })->select(['id', 'title'])->with('media')->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->get();

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

    public function store(DirectionRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;
        
        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->parent_id = $request->parent_id;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        // Приклепление медиа
        if(isset($request->poster) && count($request->poster)) {
            HRequest::save_poster($item, $request->poster, 'direction_posters');
        }

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
        if($item = $this->model::with('media')
        ->with('childs')
        ->with('creator:id,name,surname,second_name,email,phone,position')
        ->with('editor:id,name,surname,second_name,email,phone,position')
        ->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(DirectionRequest $request, $id) {

        $item = $this->model::find($id);

        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->code = $request->code;

        !is_null($request->sort) ? $item->sort = $request->sort : $item->sort = 100;
        
        $item->parent_id = $request->parent_id;

        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = isset($request->is_published) ? $request->published_at : Carbon::now();

        $item->update();

        // Приклепление медиа
        if ($request->poster && count($request->poster)) {
            if(!$request->poster['id']) {
                $item->clearMediaCollection('direction_posters');
            }
            HRequest::save_poster($item, $request->poster, 'direction_posters');
        } else {
            $item->clearMediaCollection('direction_posters');
        } 

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}