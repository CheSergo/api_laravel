<?php

namespace App\Modules\Meetings\Venues;

use Carbon\Carbon;

// Requests
use Illuminate\Http\Request;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Filters

use App\Modules\Meetings\Venues\VenuesFilter;

// Facades
use Illuminate\Support\Facades\Validator;

// Models
use App\Http\Controllers\Controller;

// Traits
use App\Traits\Actions\ActionMethods;

class VenueAdminController extends Controller
{
    // Места проведения мероприятий
    use ActionMethods;

    public $model = 'App\Modules\Meetings\Venues\Venue';
    public $messages = [
        'create' => 'Место проведения успешно добавлено',
        'edit' => 'Редактирование места проведения',
        'update' => 'Место проведения успешно изменено',
        'delete' => 'Место проведения успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index(VenuesFilter $filter)
    {
        $items = (object) $this->model::filter($filter)->orderBy('created_at', 'DESC')
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

    public function list()
    {
        $items = (object) $this->model::select('id', 'title')->orderBy('created_at', 'DESC')->published()->get();

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
            'title' => 'required|string|max:500',
            'address' => 'string|nullable|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;
        $item->address = $request->address;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->is_published) ? $request->published_at : Carbon::now();

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['create'], $request = [
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
            'address' => 'string|nullable|max:500',
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
        $item->address = $request->address;
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->is_published) ? $request->published_at : Carbon::now();

        $item->editor_id = request()->user()->id;

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['update'], $request = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }
}
