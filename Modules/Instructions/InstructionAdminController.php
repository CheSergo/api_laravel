<?php

namespace App\Modules\Instructions;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Modules\Instructions\InstructionRequest;

// Helpers
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Filters
use App\Modules\Instructions\InstructionsFilter;

// Models
use App\Http\Controllers\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class InstructionAdminController extends Controller {

    // Инструкции
    use ActionsSaveEditItem, ActionMethods;

    public $model = 'App\Modules\Instructions\Instruction';
    public $messages = [
        'create' => 'Инструкция успешно добавлена',
        'edit' => 'Редактирование инструкции',
        'update' => 'Инструкция успешно изменена',
        'delete' => 'Инструкция успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index(InstructionsFilter $filter) {
        $items = (object) $this->model::filter($filter)->orderBy('sort', 'ASC')->whereNull('parent_id')->with('children')->with('creator')->with('editor')->get();

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

    public function list() {
        $items = (object) $this->model::orderBy('sort', 'ASC')->select('id', 'title')->with('children:id,title,parent_id')->get();

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

    public function store(InstructionRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;

        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=true);

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->body = $request->body;
        $item->description = $request->description;
        $item->parent_id = $request->parent_id;
        $item->views_count = $request->views_count;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        if(isset($request->body) && count($request->body)){
            HRequest::json_save_image($request->body, $item, 'instruction_gallery');
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
        if($item = $this->model::with(['media' => function ($query) {
            $query->orderBy('order_column', 'asc');
        }])->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(InstructionRequest $request, $id) {

        $item = $this->model::find($id);

        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $old_title = $item->title;
        $new_title = $request->title;
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=true);
        $item->title = $request->title;

        $item->editor_id = request()->user()->id;

        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->body = $request->body;
        $item->description = $request->description;
        $item->parent_id = $request->parent_id;
        $item->views_count = $request->views_count;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        if(isset($request->body) && count($request->body)){
            HRequest::json_save_image($request->body, $item, 'instruction_gallery');
        }

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

    public function uploadImageEditorJs(Request $request, $id) {

        return [
            "success" => "1",
            "file" => ['url' => 'https://api-msu.astrobl.ru//storage/posters/284922/pirate-ship.jpg'],
            "request" => $request->all(),
        ];

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }
        $img_url = $item->addMedia($request->image)->toMediaCollection('instructions')->getUrl();
        $url = "https://api-msu.astrobl.ru".$img_url;

        $item->update([
            'body' => $request->data
        ]);
        $item->refresh();

        return [
            "success" => "1",
            "file" => ['url' => $url],
        ];
    }

    public function deleteImageEditorJs($id) {
        $media = Media::find($id);
        if(!$media){
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }
        $url = "https://api-msu.astrobl.ru".$media->getUrl();

        $item = $this->model::find($media->model_id);
        $body = $item->body;

        foreach($body['blocks'] as $index => &$elem) {
            if(isset($elem['type']) && $elem['type'] == 'image') {
                if($elem['data']['file']['url'] == $url) {
                    unset($body['blocks'][$index]);
                    $media->delete();
                }
            }
        }
        $body['blocks'] = array_values($body['blocks']);
        $item->body = $body;
        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['delete'], $data = [
            'deleted',
        ]);
    }

    public function forMenu() {
        $items = $this->model::whereNull('parent_id')->select(['id', 'title', 'slug', 'sort', 'is_published'])
        ->published()
        ->with('children', function ($q) {
            $q->select(["id",'title','slug','sort','is_published','parent_id'])->published()
            ->without('editor')->without('creator');
        })
        ->orderBy('sort', 'ASC')->orderBy('title', 'ASC')
        ->get();

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

    public function show($id) {
        if($item = $this->model::with(['media' => function ($query) {
            $query->orderBy('order_column', 'asc');
        }])->with('children', function ($q) {
            $q->without('editor')->without('creator');
        })
        ->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], ['id' => 'not Found',]);
        };
    }

}