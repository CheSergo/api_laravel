<?php
namespace App\Modules\Sections;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

//use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;

// Filters
use App\Modules\Sections\SectionsFilter;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Models
use App\Modules\Sections\Section;

class SectionAdminController extends Controller
{
    use ActionMethods, ActionsSaveEditItem;

    public $model = Section::class;
    public $messages = [
        'create' => 'Раздел успешно добавлен',
        'edit' => 'Редактирование раздела',
        'update' => 'Раздел успешно изменен',
        'delete' => 'Раздел успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    public function index(SectionsFilter $filter) {
        $items = (object) $this->model::filter($filter)->thisSite()
        ->whereNull('parent_id')
        ->with('user')
        ->withCount('documents')
        ->withCount('media')
        ->with('childs')
        ->orderBy('sort', 'ASC')->get();

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

    /**
     * @return array
     * Используется рекурсивный метод sectionsWithRelations из модели Section
     */
    public function list() {
        $items = (object) $this->model::thisSite()->published()->whereNull('parent_id')
        ->select('id', 'title')
        ->with('children:id,title,parent_id')
        ->orderBy('created_at', 'DESC')->get();

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
    
    public function store(BaseRequest $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:600',
            'slug' => 'string|nullable|max:600',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $slug = $request->slug && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);
        $item->reroute = $request->reroute;

        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        // Arrays
        $item->body = $request->body;
        $item->redirect = $request->redirect;
        $item->video = $request->video;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->site_id = request()->user()->active_site_id;

        $item->parent_id = $request->parent_id;
        $item->is_show = !empty($request->is_show) ? 1 : 0;
        $item->is_deleting_blocked = !empty($request->is_deleting_blocked) ? 1 : 0;
        $item->is_editing_blocked = !empty($request->is_editing_blocked) ? 1 : 0;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Приклепление медиа
        HRequest::save_gallery($item, $request->gallery, "section_gallery");

        // Прикрепленеие документов
        if(isset($request->documents) && count($request->documents)) {
            $item->documents()->attach($request->documents);
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
    public function edit($id)
    {
        if($item = $this->model::with('user')
        ->with('documents')
        ->with('media', function($q) {
            $q->orderBy('order_column', 'asc');
        })
        ->with('childs')
        ->with('components')
        ->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(BaseRequest $request, $id) {
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:600',
            'slug' => 'string|nullable|max:600',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $old_title = $item->title;
        $new_title = $request->title;
        $slug = $request->slug && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=false);
        $item->reroute = $request->reroute;

        $item->title = $request->title;

        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        // Arrays
        $item->body = $request->body;
        $item->redirect = $request->redirect;
        $item->video = $request->video;

        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;

        $item->parent_id = $request->parent_id;
        $item->is_show = !empty($request->is_show) ? 1 : 0;
        $item->is_deleting_blocked = !empty($request->is_deleting_blocked) ? 1 : 0;
        $item->is_editing_blocked = !empty($request->is_editing_blocked) ? 1 : 0;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Приклепление медиа
        HRequest::save_gallery($item, $request->gallery, "section_gallery");
 
        // Обновление документов
        $item->sync_with_sort($item, 'documents', $request->documents, 'document_sort');


        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }
}
