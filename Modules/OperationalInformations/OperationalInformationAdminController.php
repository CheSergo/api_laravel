<?php

namespace App\Modules\OperationalInformations;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Modules\OperationalInformations\OperationalInformationRequest;

// Filters
use App\Modules\OperationalInformations\OperationalInformationsFilter;

// Models
use App\Modules\OperationalInformations\OperationalInformation;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

class OperationalInformationAdminController extends Controller {
    
    // Оперативная информация
    
    use ActionMethods;

    public $model = OperationalInformation::class;
    public $messages = [
        'create' => 'Оперативная информация успешно добавлена',
        'edit' => 'Редактирование Оперативной информации',
        'update' => 'Оперативная информация успешно изменена',
        'delete' => 'Оперативная информация успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

    public function index(OperationalInformationsFilter $filter)
    {

        $builder = (object) $this->model::filter($filter)->thisSite()->orderBy('created_at', 'DESC')
        ->with('creator')
        ->with('documents')
        ->with('type')
        ->with('sources');

        $for_meta = (object) $builder->get();
        $items = (object) $builder->paginate(10)->toArray();

        $unique_sources = Meta::processItems($for_meta->pluck('sources')->toArray(), 'id', ['id', 'title']);
        Meta::sorting_by_title($unique_sources);

        if(isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        $meta['sources'] = $unique_sources;

        return ApiResponse::onSuccess(200, 'success', $data = $items->data, $meta);

        // return [
        //     'meta' => $meta,
        //     'data' => $items->data,
        // ];
    }

    public function list() {

        $items = (object) $this->model::thisSite()->select('id', 'title')->orderBy('created_at', 'DESC')->get();

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

    public function store(OperationalInformationRequest $request) {
        $item = new $this->model;

        $item->title = $request->title;
        $item->body = $request->body;
        $item->redirect = $request->redirect;

        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);
        
        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        $item->type_id = $request->type_id;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Прикрепление тэгов
        if(!empty($request->tags)) {
            $tags_array = HRequest::tags($request->tags);
            $item->tags()->attach($tags_array);
        }

        // Прикрепленеие документов
        if($request->documents && is_array($request->documents) && count($request->documents)) {
            foreach($request->documents as $index => $document) {
                $item->documents()->attach($document, ['document_sort' => $index+1]);
            }
        }

        if ($request->sources && is_array($request->sources) && count($request->sources)) {
            $item->sources()->attach($request->sources);
        }
        
        return ApiResponse::onSuccess(200, $this->messages['create'], $item);
    }

    public function edit($id) {
        $item = $this->model::with('creator')->with('editor')
        ->with('type')
        ->with('documents')
        ->with('tags')
        ->with('sources')
        ->find($id);

        if($item) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(OperationalInformationRequest $request, $id) {
        $item = $this->model::find($id);
        if(!$item){
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->body = $request->body;
        $item->redirect = $request->redirect;

        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);
        
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        $item->type_id = $request->type_id;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Прикрепления тэгов
        if(!empty($request->tags)) {
            $tags_array = HRequest::tags($request->tags);
            $item->tags()->sync($tags_array);
        }

        // Обновление документов
        if(!empty($request->documents)) {
            $item->sync_with_sort($item, 'documents', $request->documents, 'document_sort');
        }

        if (!empty($request->sources)) {
            $item->sources()->sync($request->sources);
        }
        
        return ApiResponse::onSuccess(200, $this->messages['create'], $item);
    }

}