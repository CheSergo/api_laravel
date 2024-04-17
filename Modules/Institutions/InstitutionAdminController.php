<?php

namespace App\Modules\Institutions;

use App\Http\Controllers\Controller;

// Requests
use Illuminate\Http\Request;
use App\Modules\Institutions\InstitutionRequest;
// use App\Http\Requests\BaseRequest;

// Filters
use App\Modules\Institutions\InstitutionsFilter;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HString;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Institutions\Institution;

class InstitutionAdminController extends Controller 
{
    use ActionMethods, ActionsSaveEditItem;

    // Подведомствнное учреждение
    
    public $model = Institution::class;
    public $messages = [
        'create' => 'Подведомствнное учреждение успешно добавлено',
        'edit' => 'Редактирование подведомствнного учреждения',
        'update' => 'Подведомствнное учреждение успешно изменен',
        'delete' => 'Подведомствнное учреждение успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    public function index(InstitutionsFilter $filter) {
        $items = (object) $this->model::filter($filter)->thisSite()
        ->orderBy('created_at', 'DESC')
        ->with('creator:id,name,surname,second_name,email,phone,position')
        ->with('editor:id,name,surname,second_name,email,phone,position')
        ->with('documents')
        ->with('type')
        ->paginate(10)
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

    public function list() {
        $items = (object) $this->model::thisSite()->orderBy('sort', 'ASC')->select('id', 'title')->get();

        $meta = [];
        return [
            'meta' => $meta,
            'data' => $items,
        ];
    }

    public function store(InstitutionRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;

        // Создание слага из первых букв каждого слова из title, исключая предлоги (т.е. слова короче 3х букв)
        $string = preg_replace('/[^\p{L}\s]/u', '', $item->title);
        $arr = explode(" ", $string);
        $string = '';
        foreach ($arr as $str) {
            if(mb_strlen($str) > 3) {
                $string = $string.mb_substr($str, 0, 1);
            }
        }
        $item->slug = HString::transliterate(mb_strtolower($string));

        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->about = $request->about;
        $item->email = $request->email;
        $item->address = $request->address;
        $item->phone = $request->phone;
        $item->fax = $request->fax;

        if (!empty($request->site)) {
            $item->site = !preg_match("/^http(s)?:\/\//", $request->site) ? "http://" . $request->site : $request->site;
        }

        if (!empty($request->bus_gov)) {
            $item->bus_gov = !preg_match("/^http(s)?:\/\//", $request->bus_gov) ? "http://" . $request->bus_gov : $request->bus_gov;
        }
        
        $item->type_id = $request->type_id;
        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Прикрепленеие рабов
        if($request->workers && is_array($request->workers) && count($request->workers)) {
            foreach($request->workers as $index => $worker) {
                $item->workers()->attach($worker, ['worker_sort' => $index+1]);
            }
        }

        // Прикрепленеие документов
        if($request->documents && is_array($request->documents) && count($request->documents)) {
            foreach($request->documents as $index => $document) {
                $item->documents()->attach($document, ['document_sort' => $index+1]);
            }
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = $item);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function edit($id) {
        if($item = $this->model::with('creator:id,name,surname,second_name,email,phone,position')
        ->with('editor:id,name,surname,second_name,email,phone,position')
        ->with('documents')
        ->with('workers')
        ->with('type')
        ->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(InstitutionRequest $request, $id) {

        $item = $this->model::find($id);

        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;

        // Создание слага из первых букв каждого слова из title, исключая предлоги (т.е. слова короче 3х букв)
        $string = preg_replace('/[^\p{L}\s]/u', '', $item->title);
        $arr = explode(" ", $string);
        $string = '';
        foreach ($arr as $str) {
            if(mb_strlen($str) > 3) {
                $string = $string.mb_substr($str, 0, 1);
            }
        }
        $item->slug = HString::transliterate(mb_strtolower($string));
        
        $item->sort = !is_null($request->sort) ? $request->sort : 100;
        $item->about = $request->about;
        $item->email = $request->email;
        $item->address = $request->address;
        $item->phone = $request->phone;
        $item->fax = $request->fax;

        if (!empty($request->site)) {
            $item->site = !preg_match("/^http(s)?:\/\//", $request->site) ? "http://" . $request->site : $request->site;
        }

        if (!empty($request->bus_gov)) {
            $item->bus_gov = !preg_match("/^http(s)?:\/\//", $request->bus_gov) ? "http://" . $request->bus_gov : $request->bus_gov;
        }

        $item->type_id = $request->type_id;
        $item->editor_id = request()->user()->id;
        $item->site_id = request()->user()->active_site_id;
        
        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->save();

        // Обновление рабов
        $item->sync_with_sort($item, 'workers', $request->workers, 'worker_sort');

        // Обновление документов
        $item->sync_with_sort($item, 'documents', $request->documents, 'document_sort');

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);
    }
}