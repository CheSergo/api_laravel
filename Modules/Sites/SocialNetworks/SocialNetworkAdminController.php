<?php

namespace App\Modules\Admin\SocialNetworks;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

// Models
use App\Modules\Sites\SocialNetworks\SocialNetwork;

class SocialNetworkAdminController extends Controller
{
    public $model = SocialNetwork::class;
    public $messages = [
        'create' => 'Соц.сеть успешно добавлена',
        'edit' => 'Редактирование соц.сети',
        'update' => 'Соц.сеть успешно изменена',
        'delete' => 'Соц.сеть успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index()
    {
        $items = (object) $this->model::thisSite()->orderBy('title', 'ASC')
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

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'link' => 'string|max:500|nullable',
            'icon' => 'string|max:255|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->link = $request->link;
        $item->icon = $request->icon;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = isset($request->is_published) ? $request->published_at : Carbon::now();

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->site_id = request()->user()->active_site_id;
        
        $item->save();

        $item->modules()->attach($request->module_id);

        // Возврат данных в ответе
        $data = [
            'id' => $item->id,
            'title' => $item->title,
            'code' => $item->code,
        ];

        return ApiResponse::onSuccess(200, $this->messages['create'], $data);
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
            'title' => 'required|string|max:191',
            'link' => 'string|max:500|nullable',
            'icon' => 'string|max:255|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->link = $request->link;
        $item->icon = $request->icon;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = isset($request->is_published) ? $request->published_at : Carbon::now();

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->site_id = request()->user()->active_site_id;
        
        $item->save();

        $item->modules()->sync($request->module_id);

        // Возврат данных в ответе
        $data = [
            'id' => $item->id,
            'title' => $item->title,
            'code' => $item->code,
        ];

        return ApiResponse::onSuccess(200, $this->messages['update'], $data);
    }
}
