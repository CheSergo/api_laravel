<?php

namespace App\Modules\Commissions;

use App\Http\Controllers\Controller;

// Requests
use App\Modules\Commissions\CommissionRequest;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

//Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Models
use App\Modules\Commissions\Commission;

class CommissionAdminController extends Controller {

    use ActionMethods, ActionsSaveEditItem;
    
    public $model = Commission::class;
    public $messages = [
        'create'    => 'Орган успешно добавлен',
        'edit'      => 'Редактирование органа',
        'update'    => 'Орган успешно изменен',
        'delete'    => 'Орган успешно удален',
        'not_found' => 'Орган не найден',
    ];

  /**
     * @return mixed
     */
    public function index()
    {
        $items = (object) $this->model::thisSite()
        ->orderBy('created_at', 'desc')
        ->with('site')
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

    public function store(CommissionRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;

        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);

        $item->body = $request->body;
        $item->period_meeting = $request->period_meeting;
        $item->info = $request->info;
        $item->redirect = $request->redirect;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // $item->departments()->attach($request->departments);
        // $item->directions()->attach($request->directions);
        // $item->sources()->attach($request->sources);

        if($request->heads && count($request->heads)) {
            foreach($request->heads as $index => $head_id){
                $item->heads()->attach($head_id, ['head_sort' => $index+1]);
            }
        }

        if($request->members && count($request->members)) {
            foreach($request->members as $index => $member_id){
                $item->members()->attach($member_id, ['member_sort' => $index+1]);
            }
        }

        // Прикрепленеие документов
        if($request->documents && is_array($request->documents) && count($request->documents)) {
            foreach($request->documents as $index => $document) {
                $item->documents()->attach($document, ['document_sort' => $index+1]);
            }
        }

        return ApiResponse::onSuccess(201, $this->messages['create'], $request = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

    public function edit($id) {
        if($item = $this->model::with('creator')->with('editor')
        ->with('heads', function ($q) {
            $q->orderByPivot('head_sort', 'asc');
        })
        ->with('members', function ($q) {
            $q->orderByPivot('member_sort', 'asc');
        })
        ->with('documents')
        // ->with('departments')
        // ->with('directions')
        // ->with('sources')
        ->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $request = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(CommissionRequest $request, $id) {

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $old_title = $item->title;
        $new_title = $request->title;
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=false);

        $item->title = $request->title;

        $item->body = $request->body;
        $item->period_meeting = $request->period_meeting;
        $item->info = $request->info;
        $item->redirect = $request->redirect;

        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // $item->departments()->sync($request->departments);
        // $item->directions()->sync($request->directions);
        // $item->sources()->sync($request->sources);

        $item->sync_with_sort($item, 'heads', $request->heads, 'head_sort');
        $item->sync_with_sort($item, 'members', $request->members, 'member_sort');

        // Обновление документов
        $item->sync_with_sort($item, 'documents', $request->documents, 'document_sort');

        return ApiResponse::onSuccess(201, $this->messages['update'], $request = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}