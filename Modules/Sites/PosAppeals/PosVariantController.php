<?php
namespace App\Modules\Sites\PosAppeals;
use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;

// Facades
use Illuminate\Support\Facades\Validator;

// Helpers
use App\Helpers\Meta;
use App\Helpers\HString;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

// Models
use App\Modules\Sites\PosAppeals\PosVariant;

class PosVariantController extends Controller
{
    // Места проведения мероприятий

    use ActionMethods;

    public $model = PosVariant::class;
    public $messages = [
        'create' => 'Тема виджета ПОС проведения успешно добавлено',
        'edit' => 'Редактирование темы виджета ПОС',
        'update' => 'Тема виджета ПОС успешно изменено',
        'delete' => 'Тема виджета ПОС успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index()
    {
        $items = (object) $this->model::orderBy('created_at', 'DESC')
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

    /**
     * @return mixed
     */
    public function list()
    {
        $items = (object) $this->model::orderBy('sort', 'ASC')->get();

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
            'code' => 'string|nullable|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;
        // $item->code = HString::transliterate($request->title);
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->is_published = !empty($request->is_published) ? 1 : 0;

        $item->save();

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
            'title' => 'required|string|max:255',
            'code' => 'string|nullable|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->code = $request->code;
        // $item->code = HString::transliterate($request->title);
        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->is_published = !empty($request->is_published) ? 1 : 0;

        $item->save();

        // Возврат данных в ответе
        $data = [
            'id' => $item->id,
            'title' => $item->title,
            'code' => $item->code,
        ];

        return ApiResponse::onSuccess(200, $this->messages['update'], $data);
    }
    
}
