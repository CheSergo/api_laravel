<?php

namespace App\Modules\Menus;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Menus\Menu;
use App\Modules\Modules\Module;
use App\Modules\Menus\MenuTypes\MenuType;

// Traits
use App\Traits\Actions\ActionMethods;

class MenuAdminController extends Controller
{

    use ActionMethods;
    public $model = Menu::class;
    public $messages = [
        'index' => 'Список',
        'create' => 'Меню успешно добавлено',
        'edit' => 'Редактирование меню',
        'update' => 'Меню успешно изменено',
        'delete' => 'Меню успешно удалено',
        'not_found' => 'Элемент не найден',
    ];

    public function index()
    {
        $items = MenuType::select('id', 'title', 'code')->with(['menus' => function (Builder $query) {
            $query->with('children', function ($query) {
                $query->orderBy('sort', 'ASC')->orderBy('title', 'ASC');
            })->where('parent_id', null)->orderBy('sort', 'ASC');
        }])->orderBy('sort', 'ASC')->get();

        if (isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        return [
            'meta' => $meta,
            'data' => $items,
        ];
    }

    public function main_old()
    {

        $items = MenuType::select('id', 'title', 'code')->with(['menus' => function (Builder $query) {
            $query->with('children', function ($query) {
                $query->orderBy('sort', 'ASC');
            })->where('is_show', true)->where('parent_id', null)->orderBy('sort', 'ASC');
        }])->orderBy('sort', 'ASC')->get();

        if (isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        return [
            'meta' => $meta,
            'data' => $items,
        ];
    }

    public function main()
    {

        $active_site_id = request()->user()->active_site_id;
        $user = request()->user();
        $user_abilities = array();
        foreach ($user->roles()->wherePivot('site_id', $active_site_id)->get() as $role) {
            foreach ($role->abilities as $ability) {
                if (!in_array($ability->code, $user_abilities)) {
                    array_push($user_abilities, $ability->code);
                }
            }
        }

        // РАЗРЕШЕННЫЕ МОДУЛИ
        $modules = Module::whereHas('sites', function ($q) use ($active_site_id) {
            $q->whereIn('id', [$active_site_id]);
        })->whereHas('abilities', function ($qe) use ($user_abilities) {
            $qe->whereIn('code', $user_abilities);
        })->select('id', 'title')->with('menus:id,type_id')->get()->pluck('menus');

        // Массив айдишников разрешенных сайтов
        $menus_ids = [];
        foreach ($modules as $module) {
            foreach ($module as $item) {
                array_push($menus_ids, $item['id']);
            }
        }
        // Проверка на админа, доступ к системному меню
        // if (request()->user()->tokenCan('system_index')) {
        if (in_array('system_index', $user_abilities)) {
            $type = MenuType::where('code', 'systems')->first();
            foreach ($type->menus->pluck('id') as $id) {
                array_push($menus_ids, $id);
            }
        }
        // Проверка на админа, доступ к меню справочников
        // if (request()->user()->tokenCan('references_index')) {
        if (in_array('system_guides_index', $user_abilities)) {
            $type = MenuType::where('code', 'system-guides')->first();
            foreach ($type->menus->pluck('id') as $id) {
                array_push($menus_ids, $id);
            }
        }

        //Вывод меню
        $items = MenuType::select('id', 'title', 'code')->with('menus', function ($q) use ($menus_ids) {
            $q->whereNull('parent_id')->select('id', 'title', 'path', 'icon', 'type_id')->whereIn('id', $menus_ids)->with('children', function ($q) {
                $q->select('id', 'title', 'path', 'icon', 'parent_id')->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->showed();
            })->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->showed();
        })->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->get();

        if (isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        return ApiResponse::onSuccess(200, $this->messages['index'], $data = $items);
    }

    public function list()
    {

        $items = (object) $this->model::where('parent_id', null)->with('children', function ($query) {
            $query->orderBy('sort', 'asc');
        })->orderBy('sort', 'asc')->get();

        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function item($path = null)
    {

        $item = !$path ? '' : $this->model::where('path', $path)->with('children', function ($query) {
            $query->orderBy('sort', 'asc');
        })->first();

        return response()->json($item);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'path' => 'string|max:255|nullable',
            'type_id' => 'required|numeric',
            'modules' => 'required|array',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = new $this->model;

        $item->title = $request->title;
        $item->path = $request->path;
        $item->type_id = $request->type_id;
        $item->parent_id = $request->parent_id;
        $item->icon = $request->icon;

        $item->is_show = !empty($request->is_show) ? 1 : 0;
        !is_null($request->sort) ? $item->sort = $request->sort : $item->sort = 100;

        $item->save();

        if (isset($request->modules) && is_array($request->modules)) {
            $item->modules()->attach($request->modules);
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        if ($item = $this->model::with('children')->with('modules')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'path' => 'string|max:255|nullable',
            'type_id' => 'required|numeric',
            'modules' => 'required|array',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        if ($item = $this->model::find($id)) {
            $item->title = $request->title;
            $item->path = $request->path;
            $item->type_id = $request->type_id;
            $item->parent_id = $request->parent_id;
            $item->icon = $request->icon;

            $item->sort = isset($request->sort) ? $request->sort : 100;
            $item->is_show = !empty($request->is_show) ? 1 : 0;

            $item->save();

            if (isset($request->modules) && is_array($request->modules)) {
                $item->modules()->sync($request->modules);
            }

            return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }
    }
}
