<?php
namespace App\Modules\Departments\DepartmentTypes;

use App\Http\Controllers\Controller;

// Facades
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
// use App\Helpers\HString;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

// Models
use App\Modules\Departments\DepartmentTypes\DepartmentType;

class DepartmentTypeAdminController extends Controller 
{
    use ActionMethods;
    
    public $model = DepartmentType::class;
    public $messages = [
        'create' => 'Тип структурного подразделения успешно добавлен',
        'edit' => 'Редактирование структурного подразделения',
        'update' => 'Тип структурного подразделения успешно изменен',
        'delete' => 'Тип структурного подразделения успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    public function __construct() {
        //
    }

    /**
     * @return mixed
     */
    public function index() {

        $items = (object) $this->model::orderBy('sort', 'asc')
            ->paginate(21)
            ->toArray();

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
        $items = $this->model::published()->orderBy('sort', 'asc')->get();

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
            'code' => 'required|string|max:500',
        ]);
 
        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;
        !is_null($request->sort) ? $item->sort = $request->sort : $item->sort = 100;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

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

    public function update(BaseRequest $request, $id) {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:500',
        ]);
 
        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);

        $item->title = $request->title;
        $item->code = $request->code;
        !is_null($request->sort) ? $item->sort = $request->sort : $item->sort = 100;

        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->update();

        return ApiResponse::onSuccess(200, $this->messages['edit'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}