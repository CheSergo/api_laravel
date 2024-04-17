<?php

namespace App\Modules\Directions;

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
use App\Modules\Directions\Direction;
use App\Modules\Directions\DirectionTypes\DirectionType;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class DirectionAdminController extends Controller
{
    use ActionMethods, ActionsSaveEditItem;

    public $model = Direction::class;
    public $messages = [
        'create' => 'Направление деятельности успешно добавлено',
        'edit' => 'Редактирование Направления деятельности',
        'update' => 'Направление деятельности успешно изменено',
        'delete' => 'Направление деятельности успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index(DirectionsFilter $filter)
    {
        $items = DirectionType::select(['id', 'title'])->where('parent_id', null)
            ->whereHas('directions', function ($q) {
                $q->where('site_id', request()->user()->active_site_id);
            })
            ->with('direction_types')
            ->with('directions', function ($qe) {
                $qe->where('site_id', request()->user()->active_site_id)
                    ->where('parent_id', null)
                    ->with('media')
                    ->with('documents')
                    ->with('childs')
                    ->with('creator:id,name,surname,second_name,email,phone,position')
                    ->with('editor:id,name,surname,second_name,email,phone,position')
                    ->orderBy('sort', 'ASC')
                    ->orderBy('title', 'ASC');
            })->get();

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

    /**
     * @return mixed
     */
    public function list()
    {

        /*$items = (object) $this->model::published()->thisSite()->select('id', 'title', 'type_id', 'parent_id')->orderBy('title', 'ASC')->get();*/

        // $items = (object) DirectionType::whereHas('directions', function($q) {
        //     $q->thisSite()->published();
        // })->select(['id', 'title'])->with('media')->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->get();

        $items = (object) $this->model::thisSite()->published()->whereNull('parent_id')
            ->select('id', 'title', 'type_id', 'parent_id')
            ->with('children:id,title,parent_id')
            ->orderBy('created_at', 'DESC')->get();

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

    public function store(DirectionRequest $request)
    {

        $item = new $this->model;

        $item->title = $request->title;
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type = 'slug', $old_title = null, $new_title = null, $global = false);

        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->reroute = $request->reroute;
        $item->body = $request->body;
        $item->parent_id = $request->parent_id;
        $item->section_id = $request->section_id;
        $item->redirect = $request->redirect;
        $item->video = $request->video;
        $item->type_id = $request->type_id;

        $item->is_deleting_blocked = !empty($request->is_deleting_blocked) ? 1 : 0;
        $item->is_editing_blocked = !empty($request->is_editing_blocked) ? 1 : 0;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Приклепление медиа
        // HRequest::save_gallery($request->direction_gallery, $item, "direction_gallery");
        HRequest::save_gallery($item, $request->gallery, "direction_gallery");

        // Прикрепленеие документов
        if($request->documents && is_array($request->documents) && count($request->documents)) {
            foreach($request->documents as $index => $document) {
                $item->documents()->attach($document, ['document_sort' => $index+1]);
            }
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = $item);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        if ($item = $this->model::with('media')->with('documents')->with('childs')
            ->with('creator:id,name,surname,second_name,email,phone,position')
            ->with('editor:id,name,surname,second_name,email,phone,position')
            ->find($id)
        ) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(DirectionRequest $request, $id)
    {

        $item = $this->model::find($id);

        if (!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $old_title = $item->title;
        $new_title = $request->title;
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type = 'slug', $old_title, $new_title, $global = false);

        $item->title = $request->title;

        !is_null($request->sort) ? $item->sort = $request->sort : $item->sort = 100;

        $item->body = $request->body;
        $item->reroute = $request->reroute;
        $item->parent_id = $request->parent_id;
        $item->section_id = $request->section_id;
        $item->redirect = $request->redirect;
        $item->video = $request->video;
        $item->type_id = $request->type_id;

        $item->is_deleting_blocked = !empty($request->is_deleting_blocked) ? 1 : 0;
        $item->is_editing_blocked = !empty($request->is_editing_blocked) ? 1 : 0;

        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->update();

        // Обновление фотогалереи
        // HRequest::update_gallery($request->direction_gallery, $item, "direction_gallery");
        HRequest::save_gallery($item, $request->gallery, "direction_gallery");

        // Обновление документов
        $item->sync_with_sort($item, 'documents', $request->documents, 'document_sort');

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);
    }
}
