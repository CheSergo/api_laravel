<?php

namespace App\Modules\InformationSystems;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;
use App\Modules\InformationSystems\InformationSystemRequest;

// Filters
use App\Modules\InformationSystems\InformationSystemsFilter;

// Models
use App\Modules\InformationSystems\InformationSystem;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

class InformationSystemAdminController extends Controller {
    
    // Информационная система
    
    use ActionMethods;

    public $model = InformationSystem::class;
    public $messages = [
        'create' => 'Информационная система успешно добавлена',
        'edit' => 'Редактирование Информационной системы',
        'update' => 'Информационная система успешно изменена',
        'delete' => 'Информационная система успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

    public function index(InformationSystemsFilter $filter)
    {
        $items = (object) $this->model::filter($filter)->thisSite()->orderBy('created_at', 'DESC')
        ->with('creator')
        ->with('owner')
        ->with('documents')
        ->paginate(10)
        ->toArray();

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

    public function store(InformationSystemRequest $request) {

        // $validator = Validator::make($request->all(), [
        //     'title' => 'required|string|max:500',
        //     'workers_amount' => 'integer|nullable',
        //     'exploitation_year' => 'integer|nullable',
        //     'certificate_date' => 'date|nullable',
        //     'link' => 'string|max:255|nullable',
        // ]);

        // if ($validator->fails()) {
        //     return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        // }

        $item = new $this->model;

        $item->title = $request->title;
        $item->short_title = $request->short_title;
        $item->description = $request->description;
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);

        $item->exploitation_year = $request->exploitation_year;
        $item->link = $request->link;
        $item->certificate_date = $request->certificate_date;
        $item->owner_id = $request->owner_id;
        
        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        
        $item->sort = $request->sort;
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Прикрепленеие связанных ИС
        /* 
        ** Связь many-to-many к самой себе через смежную таблицу
        ** Поэтому обратная связь обрабатывается вручную    
        */
        if(!empty($request->information_systems) && is_array($request->information_systems)) {
            $item->information_systems()->attach($request->information_systems);

            foreach($item->information_systems as $information_system) {
                $information_system->information_systems()->syncWithoutDetaching($item->id);
            }
        }

        // Прикрепленеие документов
        if(!empty($request->documents) && is_array($request->documents)) {
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
        $item = $this->model::with('creator')->with('editor')
        ->with('documents')
        ->with('information_systems')
        ->find($id);

        if($item) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(InformationSystemRequest $request, $id) {

        // $validator = Validator::make($request->all(), [
        //     'title' => 'required|string|max:500',
        //     'workers_amount' => 'integer|nullable',
        //     'exploitation_year' => 'integer|nullable',
        //     'certificate_date' => 'date|nullable',
        //     'link' => 'string|max:255|nullable',
        // ]);

        // if ($validator->fails()) {
        //     return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        // }
        
        $item = $this->model::find($id);
        if(!$item){
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }
        
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $old_title = $item->title;
        $new_title = $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=false);
        $item->title = $request->title;
        $item->short_title = $request->short_title;
        $item->description = $request->description;

        $item->exploitation_year = $request->exploitation_year;
        $item->link = $request->link;
        $item->certificate_date = $request->certificate_date;
        $item->owner_id = $request->owner_id;
        
        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        
        $item->sort = $request->sort;
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Обновление связанных ИС
        /* 
        ** Связь many-to-many к самой себе через смежную таблицу
        ** Поэтому обратная связь обрабатывается вручную    
        */
        $information_systems = $item->information_systems->pluck('id')->toArray();
        $old_relations = array_diff($information_systems, $request->information_systems);               //Нахождение id с которыми удалена связь для удаления обратной связи
        foreach($old_relations as $information_system_id) {
            InformationSystem::find($information_system_id)->information_systems()->detach($item->id);  //Удаление обратной связи
        }
        $item->information_systems()->sync($request->information_systems);
        $item = $item->fresh('information_systems');
        foreach($item->information_systems as $information_system) {
            $information_system->information_systems()->syncWithoutDetaching($item->id);                //Добавление обратной связи, если её нет
        }

        // Обновление документов
        $item->sync_with_sort($item, 'documents', $request->documents, 'document_sort');
        
        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}