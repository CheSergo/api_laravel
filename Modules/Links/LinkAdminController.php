<?php

namespace App\Modules\Links;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

// Facades
use Illuminate\Support\Facades\Validator;

// Requests
use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;
use App\Modules\Links\LinksFilter;

// Helpers
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

// Models
use App\Modules\Links\Link;

class LinkAdminController extends Controller
{
    use ActionMethods;

    public $filter;
    public $model = Link::class;
    public $messages = [
        'create' => 'Ссылка успешно добавлена',
        'edit' => 'Редактирование ссылки',
        'update' => 'Ссылка успешно изменена',
        'delete' => 'Ссылка успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

    public function __construct(LinksFilter $filter) {
        $this->filter = $filter;
    }

    public function index() {
        $items = (object) $this->model::filter($this->filter)
            ->orderBy('created_at', 'DESC')
            ->thisSite()
            ->with('creator')
            ->with('editor')
            ->with('type')
            ->with('section')
            ->with('media')
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

    public function store(BaseRequest $request) {

       $validator = Validator::make($request->all(), [
           'title' => 'required|string|max:1000',
           'description' => 'string|max:1000|nullable',
           'link' => 'string|max:1000|nullable',
       ]);

       if ($validator->fails()) {
           return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
       }

        $item = new $this->model;

        $item->title =$request->title;
        $item->description = $request->description;
        $item->redirect = $request->redirect;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        
        $item->type_id = $request->type_id;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        
        $item->site_id = request()->user()->active_site_id;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ?$request->published_at : Carbon::now();

        $item->save();

        // Създати образ
        // HRequest::save_media($request->link_posters, $item, "link_posters");
        if(isset($request->poster) && count($request->poster)) {
            HRequest::save_poster($item, $request->poster, 'link_posters');
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $item);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function edit($id) {
        if($item = $this->model::with('media')->with('type')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(BaseRequest $request, $id) {

        $validator = Validator::make($request->all(), [
           'title' => 'required|string|max:1000',
           'description' => 'string|max:1000|nullable',
           'link' => 'string|max:1000|nullable',
       ]);

       if ($validator->fails()) {
           return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
       }

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title =$request->title;
        $item->description = $request->description;
        $item->redirect = $request->redirect;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        
        $item->type_id = $request->type_id;

        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ?$request->published_at : Carbon::now();

        $item->save();

        // HRequest::update_media($request->link_posters, $item, 'link_posters');
        if ($request->poster && count($request->poster)) {
            if(!$request->poster['id']) {
                $item->clearMediaCollection('link_posters');
            }
            HRequest::save_poster($item, $request->poster, 'link_posters');
        } else {
            $item->clearMediaCollection('link_posters');
        }

        return ApiResponse::onSuccess(200, $this->messages['update'], $item);
    }

}