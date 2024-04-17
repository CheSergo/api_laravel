<?php

namespace App\Modules\Vacancies;

use App\Http\Controllers\Controller;

// Requests
use Illuminate\Http\Request;

// Facades
use Illuminate\Support\Facades\Validator;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

//Models
use App\Modules\Vacancies\Vacancy;

// Traits
use App\Traits\Actions\ActionMethods;

class VacancyAdminController extends Controller 
{
    use ActionMethods;

    public $model = Vacancy::class;
    public $messages = [
        'create' => 'Вакансия успешно добавлена',
        'edit' => 'Редактирование вакансии',
        'update' => 'Вакансия успешно изменена',
        'delete' => 'Вакансия успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

  /**
     * @return mixed
     */
    public function index(Request $request)
    {
        $items = (object) $this->model::thisSite()->orderBy('created_at', 'DESC')
        ->with('creator')
        ->with('editor')
        ->with('site')
        ->with('documents')
        ->paginate(10)
        ->toArray();

        // dd($this->model::get());

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

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;

        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->site_id = request()->user()->active_site_id;

        $item->save();

        HRequest::save_docs($request->documents, $item);

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);

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
            'title' => 'required|string|max:500',
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
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=false);

        $item->title = $request->title;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->editor_id = request()->user()->id;

        $item->site_id = request()->user()->active_site_id;

        $item->save();

        HRequest::update_docs($request->documents, $item);

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);

    }

}