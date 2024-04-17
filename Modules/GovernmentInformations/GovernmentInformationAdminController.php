<?php

namespace App\Modules\GovernmentInformations;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Modules\GovernmentInformations\GovernmentInformationRequest;

// Filters
use App\Modules\GovernmentInformations\GovernmentInformationsFilter;

// Models
use App\Modules\GovernmentInformations\GovernmentInformation;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

class GovernmentInformationAdminController extends Controller {
    
    // Информация государственных структур
    
    use ActionMethods;

    public $model = GovernmentInformation::class;
    public $messages = [
        'create' => 'Информация государственных структур успешно добавлена',
        'edit' => 'Редактирование Информации государственных структур',
        'update' => 'Информация государственных структур успешно изменена',
        'delete' => 'Информация государственных структур успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

    public function index(GovernmentInformationsFilter $filter)
    {
        $builder = (object) $this->model::filter($filter)->thisSite()->orderBy('created_at', 'DESC')
        ->with('creator')
        ->with('documents')
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

        return [
            'meta' => $meta,
            'data' => $items->data,
        ];
    }

    // public function list() {

    //     $items = (object) $this->model::thisSite()->select('id', 'title')->orderBy('created_at', 'DESC')->get();

    //     if(isset($items->path)) {
    //         $meta = Meta::getMeta($items);
    //     } else {
    //         $meta = [];
    //     }

    //     return [
    //         'meta' => $meta,
    //         'data' => $items,
    //     ];
    // }

    public function store(GovernmentInformationRequest $request) {
        $item = new $this->model;

        $item->title = $request->title;
        $item->body = $request->body;
        $item->redirect = $request->redirect;

        $slug = $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);
        
        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        
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

        // Прикрепленеие источников
        if ($request->sources && is_array($request->sources) && count($request->sources)) {
            $item->sources()->attach($request->sources);
        }
        
        return ApiResponse::onSuccess(200, $this->messages['create'], $item);
    }

    public function edit($id) {
        $item = $this->model::with('creator')->with('editor')
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

    public function update(GovernmentInformationRequest $request, $id) {
        $item = $this->model::find($id);
        if(!$item){
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->body = $request->body;
        $item->redirect = !is_null($request->redirect) ? $request->redirect : null;

        $old_title = $item->title;
        $new_title = $request->title;
        $slug = $request->title;

        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=false);
        $item->title = $request->title;

        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        
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

        // Обновление источник
        if (!empty($request->sources)) {
            $item->sources()->sync($request->sources);
        }
        
        return ApiResponse::onSuccess(200, $this->messages['create'], $item);
    }

}