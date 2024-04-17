<?php

namespace App\Modules\Articles\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Models
use App\Modules\Articles\Categories\Category;

//use App\Modules\Articles\Categories\CategoriesFilter;

class CategoryAdminController extends Controller
{
    use ActionMethods, ActionsSaveEditItem;

    /**
     * @var CategoriesFilter
     */
    public $filter;
    public $model = Category::class;
    public $messages = [
        'create' => 'Категория успешно добавлена',
        'edit' => 'Редактирование категории',
        'update' => 'Категория успешно изменена',
        'delete' => 'Категория успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * CategoryAdminController constructor.
     * @param CategoriesFilter $filter
     */
//    public function __construct(CategoriesFilter $filter) {
//        $this->filter = $filter;
//    }

    /**
     * @return mixed
     */
    public function index() {
        $items = (object) $this->model::/*filter($this->filter)
            ->*/orderBy('created_at', 'DESC')->paginate(21)->toArray();

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
        $items = $this->model::published()->select('id', 'title')->orderBy('created_at', 'DESC')->get();

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

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->sort = is_null($request->sort) ? 100 : $request->sort;
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

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
        if($item = $this->model::find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);

        $item->title = $request->title;
        $item->code = $request->code;

        $item->editor_id = request()->user()->id;

        $item->sort = is_null($request->sort) ? 100 : $request->sort;
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}