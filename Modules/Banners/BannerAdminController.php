<?php
namespace App\Modules\Banners;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon, Str;

// Requests
use Illuminate\Http\Request;

// Filters
use App\Modules\Banners\BannersFilter;

// Helpers
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Banners\Banner;

// Traits
use App\Traits\Actions\ActionMethods;

class BannerAdminController extends Controller 
{
    use ActionMethods;

    public $filter;
    public $model = Banner::class;
    public $messages = [
        'create' => 'Баннер успешно добавлен',
        'edit' => 'Редактирование баннера',
        'update' => 'Баннер успешно изменен',
        'delete' => 'Баннер успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * ArticleAdminController constructor.
     * @param BannersFilter $filter
     */
    public function __construct(BannersFilter $filter) {
        $this->filter = $filter;
    }

    public function index()
    {
        $items = (object) $this->model::filter($this->filter)->orderBy('created_at', 'DESC')
        ->with('media')
        ->with('creator')
        ->paginate(10)->toArray();;
        
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

    public function list()
    {
        $items = (object) $this->model::orderBy('sort', 'ASC')->orderBy('title', 'ASC')->select('id', 'title')->get();

        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'string|max:100|nullable',
            'description' => 'string|max:500|nullable',
            'area' => 'string|max:255|nullable',
            'class' => 'string|max:100|nullable',

            'sort' => 'integer|nullable',

            'redirect' => 'array|nullable',
            'site_types' => 'array|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;
        $item->description = $request->description;
        $item->area = $request->area;
        $item->class = $request->class;

        $item->redirect = $request->redirect;
        $item->site_types = $request->site_types;

        $item->sort = !empty($request->sort) ? $request->sort : 100;
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();
        $item->published_expired_at = !empty($request->published_expired_at) ? $request->published_expired_at : null;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->save();

        if(!empty($request->poster) && is_array($request->poster)) {
            HRequest::save_poster($item, $request->poster, 'banner_posters');
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = $item);
    }

    public function edit($id) {
        if($item = $this->model::with('media')->with('creator')->with('editor')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'code' => 'string|max:100|nullable',
            'description' => 'string|max:500|nullable',
            'area' => 'string|max:255|nullable',
            'class' => 'string|max:100|nullable',

            'sort' => 'integer|nullable',

            'redirect' => 'array|nullable',
            'site_types' => 'array|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);
        if (!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->code = $request->code;
        $item->description = $request->description;
        $item->area = $request->area;
        $item->class = $request->class;

        $item->redirect = $request->redirect;
        $item->site_types = $request->site_types;

        $item->sort = !empty($request->sort) ? $request->sort : 100;
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();
        $item->published_expired_at = !empty($request->published_expired_at) ? $request->published_expired_at : null;

        $item->editor_id = request()->user()->id;

        $item->save();

        // if(!empty($request->poster) && is_array($request->poster)) {
            // HRequest::save_poster($item, $request->poster, 'banner_posters');
        // }

        if ($request->poster && count($request->poster)) {
            if(!isset($request->poster['id'])) {
                $item->clearMediaCollection('banner_posters');
            }
            HRequest::save_poster($item, $request->poster, 'banner_posters');
        } else {
            $item->clearMediaCollection('banner_posters');
        }

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);
    }

}
