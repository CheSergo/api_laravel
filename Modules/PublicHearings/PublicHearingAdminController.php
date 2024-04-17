<?php

namespace App\Modules\PublicHearings;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseRequest;

// Filters
use App\Modules\PublicHearings\PublicHearingsFilter;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Models
use App\Modules\PublicHearings\PublicHearing;

class PublicHearingAdminController extends Controller 
{
    use ActionMethods, ActionsSaveEditItem;
    
    public $model = PublicHearing::class;
    public $messages = [
        'create' => 'Публичное слушание успешно добавлено',
        'edit' => 'Редактирование Публичного слушания',
        'update' => 'Публичное слушание успешно изменено',
        'delete' => 'Публичное слушание успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index(PublicHearingsFilter $filter) {
        $items = (object) $this->model::filter($filter)->thisSite()->orderBy('created_at', 'desc')->paginate(20)->toArray();
        
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
        $items = (object) $this->model::thisSite()->orderBy('title', 'ASC')->orderBy('title', 'ASC')->get();

        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function store(PublicHearingRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);

        $item->date_start = $request->date_start;
        $item->date_end = $request->date_end;

        $item->advertisement = $request->advertisement;
        $item->decision = $request->decision;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        // Прикрепленеие источников
        if(!empty($request->sources) && is_array($request->sources)) {
            $item->sources()->attach($request->sources);
        }

        // Прикрепленеие документов
        if($request->documents && is_array($request->documents) && count($request->documents)) {
            foreach($request->documents as $index => $document) {
                $item->documents()->attach($document, ['document_sort' => $index+1]);
            }
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

    public function edit($id) {
        if($item = $this->model::with('documents')->with('sources')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(PublicHearingRequest $request, $id) {

        $item = $this->model::find($id);

        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $old_title = $item->title;
        $new_title = $request->title;
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=false);

        $item->date_start = $request->date_start;
        $item->date_end = $request->date_end;

        $item->advertisement = $request->advertisement;
        $item->decision = $request->decision;

        $item->editor_id = request()->user()->id;

        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        // Обновление источников
        if(isset($request->sources)) {
            $item->sources()->sync($request->sources);
        }

        // Обновление документов
        $item->sync_with_sort($item, 'documents', $request->documents, 'document_sort');

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }


}