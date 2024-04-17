<?php
namespace App\Modules\Smis;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

//use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;

// Filters
use App\Modules\Smis\SmisFilter;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Models
use App\Modules\Smis\Smi;
use App\Modules\Logs\LogService;

class SmiAdminController extends Controller
{
    use ActionMethods, ActionsSaveEditItem;

    public $model = Smi::class;
    public $messages = [
        'create' => 'СМИ успешно добавлено',
        'edit' => 'Редактирование СМИ',
        'update' => 'СМИ успешно изменено',
        'delete' => 'СМИ успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    public function index(SmisFilter $filter) {
        $items = (object) $this->model::orderBy('created_at', 'desc')
            ->thisSite()
            ->with('sources')
            ->with('creator')
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
     * @return array
     * Используется рекурсивный метод sectionsWithRelations из модели Section
     */
    public function list() {
        $items = (object) $this->model::select('id', 'title')->get();

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
            'title' => 'required|string|max:500',
            'slug' => 'string|nullable|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $slug = $request->slug && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);

        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->number = $request->number;
        $item->address = $request->address;
        $item->domain = $request->domain;
        $item->specialization = $request->specialization;
        $item->distribution_type = $request->distribution_type;
        $item->area = $request->area;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();
        
        $item->registration_date = !empty($request->registration_date) ? $request->registration_date : Carbon::now();

        // $allAttributes = $item->getAttributes();

        $item->save();

        // для лога
        $log_class = new LogService;
        $allAttributes = $item->getAttributes();
        $allAttributes['relations'] = [];
        $relations = [];

        // Прикрепленеие источников
        if(isset($request->sources) && count($request->sources)) {
            $item->sources()->attach($request->sources);
            // $relations = $item->addRelationshipValues($item, 'sources');
            $relations['sources'] = $log_class->addRelationshipValues($item, 'sources');
        }

        if (!is_null($relations)) {
            foreach ($relations as $key => $relation) {
                $allAttributes['relations'][$key] = $relation;
            }
        }
        // $log = [
        //     'new' => $allAttributes,
        // ];
        $log = $log_class->createLog($allAttributes);
        $item->saveLog($item->creator_id, $item, $log, 'created');


        return ApiResponse::onSuccess(200, $this->messages['create'], $item);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        if($item = $this->model::with('sources')->with('creator')->with('editor')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(BaseRequest $request, $id) {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:500',
            'slug' => 'string|nullable|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        // для лога
        $oldAttributes = $item->getAttributes();

        $item->title = $request->title;
        $slug = $request->slug && !is_null($request->slug) ? $request->slug : $request->title;
        if ((isset($request->slug) && $slug != $oldAttributes['slug']) or (isset($request->title) && $request->title != $oldAttributes['title'])) {
            $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);
        }

        $item->sort = !is_null($request->sort) ? $request->sort : 100;

        $item->number = $request->number;
        $item->address = $request->address;
        $item->domain = $request->domain;
        $item->specialization = $request->specialization;
        $item->distribution_type = $request->distribution_type;
        $item->area = $request->area;

        $item->editor_id = request()->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();
        
        $item->registration_date = !empty($request->registration_date) ? $request->registration_date : Carbon::now();

        // для лога
        $log_class = new LogService;
        $changedAttributes = $log_class->getChangedAttributes($item);
        $oldAttributes = array_intersect_key($oldAttributes, $changedAttributes);

        // $changedAttributes = $item->getChangedAttributes();
        // $oldAttributes = array_intersect_key($oldAttributes, $changedAttributes);

        $item->save();

        // Обновление источников
        $oldSources = $log_class->getRelationshipValues($item, 'sources');
        if($request->sources && is_array($request->sources)) {
            $item->sources()->sync($request->sources);
        } else {
            $item->sources()->detach();
        }
        $newSources = $log_class->getRelationshipValues($item, 'sources');
        if ($oldSources != $newSources) {
            $changedAttributes['relations']['sources'] = $newSources;
            $oldAttributes['relations']['sources'] = $oldSources;
        }

        $log = $log_class->createLog($changedAttributes, $oldAttributes);
        $item->saveLog($item->editor_id, $item, $log, 'updated');

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);
    }
}
