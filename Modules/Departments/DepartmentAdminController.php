<?php

namespace App\Modules\Departments;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Modules\Departments\DepartmentRequest;

// Filters
use App\Modules\Departments\DepartmentsFilter;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Models
use App\Modules\Departments\Department;
use App\Modules\Departments\DepartmentTypes\DepartmentType;

class DepartmentAdminController extends Controller 
{
    use ActionMethods, ActionsSaveEditItem;

    public $model = Department::class;
    public $messages = [
        'create' => 'Структурное поздразделение успешно создано',
        'edit' => 'Редактирование структурное поздразделения',
        'update' => 'Структурное поздразделение успешно изменено',
        'delete' => 'Структурное поздразделение успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    public function index(Request $request)
    {
        $items = (object) $this->model::thisSite()->container()
            ->whereHas('type', function($query) use ($request) {
                $query->where('code', $request->type);
            })
            ->with('documents')
            ->with('workers')
            ->with('childs')
            ->with('creator:id,name,surname,second_name,email,phone,position')
            ->with('editor:id,name,surname,second_name,email,phone,position')
            ->orderBy('sort', 'asc')
            ->orderBy('created_at', 'desc')->get();
        
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
        $items = (object) $this->model::whereNull('parent_id')->thisSite()
        ->with('documents')->with('workers')
        ->with('childs')->orderBy('sort', 'ASC')->with('children:id,parent_id,title')->get();
        
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

    public function listByType($type) {

        $items = (object) $this->model::whereNull('parent_id')->whereHas('type', function($q) use ($type) {
            $q->where('code', $type);
        })->thisSite()
        ->with('documents')->with('workers')
        ->with('childs')->orderBy('sort', 'ASC')->with('children:id,parent_id,title')->get();
        
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
    
    public function store(DepartmentRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;
        $slug = $item->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);

        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->parent_id = !is_null($request->parent_id) ? $request->parent_id : null;
        $item->credentials = $request->credentials;
        $item->servicies = $request->servicies;
        $item->schedule = $request->schedule;
        $item->redirect = $request->redirect;
        $item->phone = $request->phone;
        $item->email = $request->email;
        $item->fax = $request->fax;
        $item->address = $request->address;

        $type_id = DepartmentType::where('code', $request->type)->first()->id;
        $item->type_id = $type_id;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // $item->directions()->attach($request->directions);

        // Прикрепленеие документов
        if($request->documents && is_array($request->documents) && count($request->documents)) {
            foreach($request->documents as $index => $document) {
                $item->documents()->attach($document, ['document_sort' => $index+1]);
            }
        }

        // Прикрепленеие рабов
        if($request->workers && is_array($request->workers) && count($request->workers)) {
            foreach($request->workers as $index => $worker) {
                $item->workers()->attach($worker, ['worker_sort' => $index+1]);
            }
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = $item);
    }

    public function edit($id) {
        if($item = $this->model::with('documents')
            ->with('workers')
            ->with('directions')
            ->with('childs')
            ->with('children:id,parent_id,title')
            ->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(DepartmentRequest $request, $id) {

        $item = $this->model::find($id);
        if (!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $slug = $item->title;
        $old_title = $item->title;
        $new_title = $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=false);

        $item->title = $request->title;

        !is_null($request->sort) ? $item->sort = $request->sort : $item->sort = 100;

        $item->parent_id = !is_null($request->parent_id) ? $request->parent_id : null;
        $item->credentials = $request->credentials;
        $item->servicies = $request->servicies;
        $item->redirect = $request->redirect;
        $item->schedule = $request->schedule;
        $item->phone = $request->phone;
        $item->email = $request->email;
        $item->fax = $request->fax;
        $item->address = $request->address;
        if($item->parent_id != $request->parent_id) {
            $type_id = DepartmentType::where('code', $request->type)->first()->id;
            $item->type_id = $type_id;
        }
            

        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->update();

        // if ($request->directions) {
        //     $item->directions()->sync($request->directions);
        // }

        // Обновление документов
        $item->sync_with_sort($item, 'documents', $request->documents, 'document_sort');

        // Обновление рабов
        $item->sync_with_sort($item, 'workers', $request->workers, 'worker_sort');

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);
    }

}