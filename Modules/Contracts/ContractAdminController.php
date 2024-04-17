<?php
namespace App\Modules\Contracts;

use App\Http\Controllers\Controller;

// Requests
use App\Modules\Contracts\ContractRequest;

// Filters
use App\Modules\Contracts\ContractsFilter;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

// Models
use App\Modules\Contracts\Contract;

class ContractAdminController extends Controller
{
    use ActionMethods;

    public $model = Contract::class;
    public $messages = [
        'create' => 'Контракт успешно добавлен',
        'edit' => 'Редактирование контракта',
        'update' => 'Контракт успешно изменен',
        'delete' => 'Контракт успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    public function index(ContractsFilter $filter) {
        $items = (object) $this->model::filter($filter)->orderBy('created_at', 'DESC')->with('site')->paginate(24)->toArray();

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

    public function store(ContractRequest $request) {

        $item = new $this->model;

        $item->number = $request->number;
        !is_null($request->date_start) ? $item->date_start = Carbon::parse($request->date_start)->format('Y-m-d H:i:s') : $item->date_start = null;
        !is_null($request->date_end) ? $item->date_end = Carbon::parse($request->date_end)->format('Y-m-d H:i:s') : $item->date_end = null;
        $item->comment = $request->comment;
        $item->site_id = $request->site_id;
        $item->price = $request->price;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
            'id' => $item->id,
            'number' => $item->number,
            'date_start' => $item->date_start,
            'date_end' => $item->date_end
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

    public function update(ContractRequest $request, $id) {

        $item = $this->model::find($id);

        $item->number = $request->number;
        $item->date_start = !is_null($request->date_start) ? Carbon::parse($request->date_start)->format('Y-m-d H:i:s') : null;
        $item->date_end = !is_null($request->date_end) ? Carbon::parse($request->date_end)->format('Y-m-d H:i:s') : null;
        $item->comment = $request->comment;
        $item->site_id = $request->site_id;
        $item->price = $request->price;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}
