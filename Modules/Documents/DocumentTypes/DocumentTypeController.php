<?php
namespace App\Modules\Documents\DocumentTypes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

// Filters
use App\Modules\Documents\DocumentTypes\DocumentTypeController;

// Models
use App\Modules\Documents\DocumentTypes\DocumentType;

class DocumentTypeController extends Controller {

    use ActionMethods;
    
    public $model = DocumentType::class;
    public $messages = [
        'create' => 'Тип документа успешно добавлен',
        'edit' => 'Редактирование типа документа',
        'update' => 'Тип документа успешно изменен',
        'delete' => 'Тип документа успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    public function __construct(DocumentTypesFilter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index() {
        $items = (object) $this->model::orderBy('title', 'ASC')->orderBy('created_at', 'desc')
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
        $items = $this->model::filter($this->filter)->published()->orderBy('title', 'asc')->get();

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
            'title' => 'required|max:255',
            'code' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->code = $request->code;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        
        $item->is_antimonopoly = !empty($request->is_antimonopoly) ? 1 : 0;
        $item->is_anticorruption = !empty($request->is_anticorruption) ? 1 : 0;
        $item->is_mpa = !empty($request->is_mpa) ? 1 : 0;
        $item->is_status = !empty($request->is_status) ? 1 : 0;

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
            'title' => 'required|max:255',
            'code' => 'required|max:255',
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

        $item->editor_id = request()->user()->id;
        
        $item->is_antimonopoly = !empty($request->is_antimonopoly) ? 1 : 0;
        $item->is_anticorruption = !empty($request->is_anticorruption) ? 1 : 0;
        $item->is_mpa = !empty($request->is_mpa) ? 1 : 0;
        $item->is_status = !empty($request->is_status) ? 1 : 0;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !is_null($request->is_published) ? $request->published_at : Carbon::now();

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['edit'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}