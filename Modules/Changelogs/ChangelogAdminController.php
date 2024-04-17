<?php

namespace App\Modules\Changelogs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// Helpers
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;
use Illuminate\Support\Carbon;

// Models
use App\Modules\Changelogs\Changelog;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

//Filters
use App\Modules\Changelogs\ChangelogsFilter;

// Traits
use App\Traits\Actions\ActionMethods;

class ChangelogAdminController extends Controller 
{
    use ActionMethods;

    public $model = Changelog::class;
    public $messages = [
        'create' => 'Объявление успешно добавлено',
        'edit' => 'Редактирование объявления',
        'update' => 'Объявление успешно изменено',
        'delete' => 'Объявление успешно удалено',
        'not_found' => 'Элемент не найден',
    ];
    
    public function __construct(public ChangelogsFilter $filter) {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $items = (object) $this->model::filter($this->filter)->with('creator')->orderBy('created_at', 'DESC')->paginate(10)->toArray();

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

    /**
     * @return mixed
     */
    public function list() {
        $items = (object) $this->model::published()->orderBy('published_at', 'DESC')->select('id', 'title', 'description', 'type', 'published_at')->limit(4)->get();

        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'string|max:500|nullable',
            'body' => 'array|nullable',
            'views_count' => 'integer|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->body = $request->body;
        $item->description = $request->description;
        $item->type = $request->type;
        $item->views_count = 0;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->is_pin = !empty($request->is_pin) ? 1 : 0;
        $item->pin_date = !empty($request->is_pin) ? $request->pin_date : Carbon::now();
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        if(isset($request->body) && count($request->body)){
            HRequest::json_save_image($request->body, $item, 'changelog_gallery');
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $request = [
            'id' => $item->id,
            'title' => $item->title,
        ]);

    }

    public function show($id) {
        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->timestamps = false;
        $item->increment('views_count');

        return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item); 
    }

    public function edit($id) {
        if($item = $this->model::find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'string|max:500|nullable',
            'body' => 'array|nullable',
            'views_count' => 'integer|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->body = $request->body;
        $item->description = $request->description;
        $item->type = $request->type;

        $item->editor_id = request()->user()->id;

        $item->is_pin = !empty($request->is_pin) ? 1 : 0;
        $item->pin_date = !empty($request->is_pin) ? $request->pin_date : Carbon::now();
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        if(isset($request->body) && count($request->body)){
            HRequest::json_save_image($request->body, $item, 'changelog_gallery');
        }

        return ApiResponse::onSuccess(200, $this->messages['update'], $request = [
            'id' => $item->id,
            'title' => $item->title,
        ]);

    }

}