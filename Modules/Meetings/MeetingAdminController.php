<?php

namespace App\Modules\Meetings;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Modules\Meetings\MeetingRequest;
// use App\Modules\BaseRequest;

// Filters
use App\Modules\Meetings\MeetingsFilter;

// Models
use App\Modules\Meetings\Meeting;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

class MeetingAdminController extends Controller {
    
    // Заседания
    
    use ActionMethods;

    public $model = Meeting::class;
    public $messages = [
        'create' => 'Заседание успешно добавлено',
        'edit' => 'Редактирование Заседания',
        'update' => 'Заседание успешно изменено',
        'delete' => 'Заседание успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index(MeetingsFilter $filter)
    {
        $items = (object) $this->model::filter($filter)->thisSite()->orderBy('created_at', 'DESC')
            ->with('creator')
            ->with('editor')
            ->with('venue')
            ->with('type')
            ->with('commissions')
            ->with('documents')
            ->paginate(10)->toArray();

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

    public function store(MeetingRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;
        $item->video = $request->video;
        $item->place = $request->place;
        $item->begin_time_at = !is_null($request->date) ? Carbon::parse($request->begin_time_at)->format('Y-m-d H:i:s') : null;

        $item->venue_id = $request->venue_id;
        $item->type_id = $request->type_id;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Прикрепление файлов Agenda, protocol, transcript
        if(isset($request->agenda) && count($request->agenda)) {
            $item->agenda()->attach($request->agenda[0]);
        }
        if(isset($request->protocol) && count($request->protocol)) {
            $item->protocol()->attach($request->protocol[0]);
        }
        if(isset($request->transcript) && count($request->transcript)) {
            $item->transcript()->attach($request->transcript[0]);
        }

        $item->commissions()->attach($request->commissions);
        HRequest::save_docs($request->documents, $item);

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
        
        $item = $this->model::with('commissions')
        ->with('documents')
        ->with('agenda')
        ->with('protocol')
        ->with('transcript')
        ->with('creator')
        ->with('editor')
        ->find($id);

        if($item) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(MeetingRequest $request, $id) {

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }
        
        $item->title = $request->title;
        $item->video = $request->video;
        $item->place = $request->place;
        $item->begin_time_at = !is_null($request->begin_time_at) ? Carbon::parse($request->begin_time_at)->format('Y-m-d H:i:s') : null;

        $item->venue_id = $request->venue_id;
        $item->type_id = $request->type_id;

        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Прикрепление файлов Agenda, protocol, transcript
        if(isset($request->agenda) && count($request->agenda)) {
            $item->agenda()->detach();
            $item->agenda()->attach($request->agenda[0]);
        }
        if(isset($request->protocol) && count($request->protocol)) {
            $item->protocol()->detach();
            $item->protocol()->attach($request->protocol[0]);
        }
        if(isset($request->transcript) && count($request->transcript)) {
            $item->transcript()->detach();
            $item->transcript()->attach($request->transcript[0]);
        }

        $item->commissions()->sync($request->commissions);
        HRequest::update_docs($request->documents, $item);

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
        
    }
}