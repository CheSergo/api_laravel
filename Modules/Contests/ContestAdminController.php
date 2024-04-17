<?php

namespace App\Modules\Contests;

use App\Http\Controllers\Controller;

// Requests
use Illuminate\Http\Request;
use App\Modules\Contests\ContestRequest;

// Filters
use App\Modules\Contests\ContestFilter;

// Models
use App\Modules\Contests\Contest;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class ContestAdminController extends Controller 
{
    use ActionMethods, ActionsSaveEditItem;

        /**
     * @var ContestsFilter
     */
    public $filter;
    public $model = Contest::class;
    public $messages = [
        'create' => 'Конкурс успешно добавлен',
        'edit' => 'Редактирование конкурса',
        'update' => 'Конкурс успешно изменен',
        'delete' => 'Конкурс успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * ContestAdminController constructor.
     * @param ContestFilter $filter
     */
    public function __construct(ContestFilter $filter) {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index(Request $request) {

        $items = (object) $this->model::filter($this->filter)->orderBy('created_at', 'desc')->thisSite()
            ->with('site')
            ->with('creator')
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

    public function store(ContestRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;
      
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug(
            $this->model,
            $item->id,
            $slug,
            $type='slug',
            $old_title=null,
            $new_title=null,
            $global=false);

        $item->begin_at = $request->begin_at;
        $item->end_at = $request->end_at;

        $item->announcement = $request->announcement;
        $item->acceptance = $request->acceptance;
        $item->second_phase = $request->second_phase;
        $item->results = $request->results;

        $item->creator_id = $request->user()->id;
        $item->editor_id = $request->user()->id;

        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->is_failed = !empty($request->is_failed) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Прикрепленеие документов
        if($request->documents && is_array($request->documents) && count($request->documents)) {
            foreach($request->documents as $index => $document) {
                $item->documents()->attach($document, ['document_sort' => $index+1]);
            }
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = $item);
    }

    public function edit($id) {
        $item = $this->model::with('creator')->with('editor')
            ->with('site')
            ->with('documents')
            ->find($id);

        if($item) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(ContestRequest $request, $id) {

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $old_title = $item->title;
        $new_title = $request->title;
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=false);

        $item->title = $request->title;

        $item->begin_at = $request->begin_at;
        $item->end_at = $request->end_at;

        $item->announcement = $request->announcement;
        $item->acceptance = $request->acceptance;
        $item->second_phase = $request->second_phase;
        $item->results = $request->results;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->is_failed = !empty($request->is_failed) ? 1 : 0;

        $item->save();

        // Обновление документов
        $item->sync_with_sort($item, 'documents', $request->documents, 'document_sort');

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);
    }

}