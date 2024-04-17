<?php

namespace App\Modules\Sites;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

// use Illuminate\Http\Request;
use App\Modules\Sites\SiteRequest;
use Illuminate\Http\Request;

// Filters
use App\Modules\Sites\SitesFilter;

// Helpers
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;

//Models
use App\Modules\Sites\Site;
use App\Models\User;
use App\Modules\Sites\PosAppeals\PosAppeal;
use App\Modules\Sites\SocialNetworks\SocialNetwork;

class SiteAdminController extends Controller
{
    use ActionMethods;

    public $model = Site::class;
    public $messages = [
        'create' => 'Сайт успешно добавлен',
        'edit' => 'Редактирование сайта',
        'update' => 'Сайт успешно изменен',
        'update_pos' => 'Виджеты успешно изменены',
        'update_policy' => 'Политика конфиденциальности успешно изменена',
        'delete' => 'Сайт успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * @return mixed
     */
    public function index(SitesFilter $filter)
    {
        $items = (object) $this->model::filter($filter)->orderBy('created_at', 'DESC')
            ->with('contracts', function ($query) {
                $query->orderBy('date_end', 'DESC');
            })->with('sources')->with('creator')
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
     * @param string $search
     * @return object[]
     */
    public function search_sites($search = "") {

        $user_sites = User::find(request()->user()->id)->sites->pluck('id');

        if (!empty($search)) {
            $active_site = (object)$this->model::where('id', request()->user()->active_site_id)->select('id', 'title')->get();
            $sites = (object)$this->model::whereIn('id', $user_sites)->where('title', 'like', "%$search%")->orderBy('title', 'ASC')->select('id', 'title')->get();
            $items = collect($active_site)->merge($sites);
        } else {
            $items = (object)$this->model::whereIn('id', $user_sites)->orderBy('title', 'ASC')->select('id', 'title')->get();
        }

        foreach($items as $item) {
            $item->title = strip_tags($item->title);
        }

        return [
            'data' => $items,
        ];
    }

    /**
     * @return mixed
     */
    public function list() {
        $sites = User::find(request()->user()->id)->sites->pluck('id');
        // $sites = $user->sites->pluck('id');
        $items = (object) $this->model::whereIn('id', $sites)->orderBy('title', 'ASC')->select('id', 'title')->get();

        foreach($items as $item) {
            $item->title = strip_tags($item->title);
        }

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

    public function list_all() {
        $items = (object) $this->model::select(['id', 'title'])->orderBy('title', 'ASC')->get();

        foreach($items as $item) {
            $item->title = strip_tags($item->title);
        }

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

    public function adminlist() {
        $items = (object) $this->model::orderBy('title', 'ASC')->select('id', 'title')->get();

        foreach($items as $item) {
            $item->title = strip_tags($item->title);
        }

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

    public function store(SiteRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;
        $item->domain = $request->domain;
        $item->path = $request->path;
        $item->path_old = $request->path_old;
        $item->description = $request->description;
        $item->keywords = $request->keywords;
        $item->address = $request->address;
        $item->physical_address = $request->physical_address;
        $item->fax = $request->fax;
        $item->phone = $request->phone;
        $item->email = $request->email;
        $item->inn = $request->inn;
        $item->kpp = $request->kpp;
        $item->okpo = $request->okpo;
        $item->ogrn = $request->ogrn;
        $item->okved = $request->okved;
        $item->ymetrika = $request->ymetrica;
        // $item->pos_surveys = $request->pos_surveys;
        // $item->privacy_policy = $request->privacy_policy;
        $item->type = $request->type;
        $item->district_id = $request->district_id;
        $item->alert = $request->alert;

        $item->is_search = !empty($request->is_search) ? 1 : 0;
        $item->is_test = !empty($request->is_test) ? 1 : 0;
        $item->is_rss = !empty($request->is_rss) ? 1 : 0;
        $item->is_active = !empty($request->is_active) ? 1 : 0;
        $item->ymetrika_informer = !empty($request->ymetrika_informer) ? 1 : 0;

        $item->creator_id = $request->user()->id;
        $item->editor_id = $request->user()->id;

        $item->save();

        if(isset($request->social_networks) && count($request->social_networks)) {
            foreach ($request->social_networks as $s_link) {
                $social_network = new SocialNetwork;
                $social_network->code = $s_link['code'];
                $value = $s_link['link'];
                if (!preg_match("/^http(s)?:\/\//", $value)) {
                    $value = "http://" . $value;
                }
                $social_network->link = $value;

                $social_network->creator_id = request()->user()->id;
                $social_network->editor_id = request()->user()->id;

                $social_network->save();
    
                $item->social_networks()->save($social_network);
            }
        }

        if(isset($request->modules) && is_array($request->modules)) {
            $item->modules()->attach($request->modules);
        }
        if(isset($request->link_types) && is_array($request->link_types)) {
            $item->link_types()->attach($request->link_types);
        }
        if(isset($request->sources) && is_array($request->sources)) {
            $item->sources()->attach($request->sources);
        }
        

        // if (!empty($request->pos_appeal_code)) {
        //     $pos_appeal = new PosAppeal;
        //     $pos_appeal->title = $request->pos_appeal_title;
        //     $pos_appeal->code = $request->pos_appeal_code;
        //     $pos_appeal->description = $request->pos_appeal_description;
        //     $item->pos_appeal()->save($pos_appeal);
        // }

        // HRequest::save_media($request->site_logos, $item, 'site_logos');
        HRequest::save_poster($item, $request->logo, 'site_logos');

        // Возврат данных в ответе
        $data = [
            'id' => $item->id,
            'title' => $item->title,
            'code' => $item->code,
        ];

        return ApiResponse::onSuccess(200, $this->messages['create'], $data);
    }

    public function edit($id) {
        $item = $this->model::with('media')->with('pos_appeal')->with('modules')
        ->with('link_types')->with('social_networks')
        ->with('sources')
        ->find($id);

        if($item) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(SiteRequest $request, $id) {

       //dd($request->all());

        $item = $this->model::find($id);

        $item->title = $request->title;
        $item->domain = $request->domain;
        $item->path = $request->path;
        $item->path_old = $request->path_old;
        $item->description = $request->description;
        $item->keywords = $request->keywords;
        $item->address = $request->address;
        $item->physical_address = $request->physical_address;
        $item->fax = $request->fax;
        $item->phone = $request->phone;
        $item->email = $request->email;
        $item->inn = $request->inn;
        $item->kpp = $request->kpp;
        $item->okpo = $request->okpo;
        $item->ogrn = $request->ogrn;
        $item->okved = $request->okved;
        $item->ymetrika = $request->ymetrika;
        $item->ymetrika_informer = !empty($request->ymetrika_informer) ? 1 : 0;
        $item->is_active = !empty($request->is_active) ? 1 : 0;
        $item->is_test = !empty($request->is_test) ? 1 : 0;
        $item->is_search = !empty($request->is_search) ? 1 : 0;
        $item->is_rss = !empty($request->is_rss) ? 1 : 0;
        // $item->privacy_policy = $request->privacy_policy;
        $item->type = $request->type;
        $item->district_id = $request->district_id;
        $item->alert = $request->alert;

        $item->editor_id = $request->user()->id;

        // Обновление соц. сетей
        $social_networks_ids = array();
        if (isset($request->social_networks) && count($request->social_networks)) {
            foreach ($request->social_networks as $s_link) {
                if ($s_link['code'] == "") {
                    continue;
                }
                if(isset($s_link['id']) && !is_null($s_link['id'])) {
                    $social_network = $item->social_networks->where('id', $s_link['id'])->first();
                    if($social_network) {
                        array_push($social_networks_ids, $s_link['id']);
                        $social_network->code = $s_link['code'];
                        $social_network->link = $s_link['link'];
                        $social_network->editor_id = request()->user()->id;
                        $social_network->save();
                    }
                } else {
                    $social_network = new SocialNetwork;
                    $social_network->code = $s_link['code'];
                    $value = $s_link['link'];
                    if (!preg_match("/^http(s)?:\/\//", $value)) {
                        $value = "http://" . $value;
                    }
                    $social_network->link = $value;
                    $social_network->editor_id = request()->user()->id;
                    $social_network->creator_id = request()->user()->id;
                    $social_network->save();
                    
                    array_push($social_networks_ids, $social_network->id);

                    $item->social_networks()->attach($social_network);
                }
            }
        }
        $social_networks_to_del = array_diff($item->social_networks->pluck('id')->toArray(), $social_networks_ids);
        foreach($social_networks_to_del as $link_to_del) {
            $link_to_del = $item->social_networks->where('id', $link_to_del)->first();
            $item->social_networks()->detach($link_to_del->id);
            $link_to_del->delete();
        }

        $item->update();

        if(isset($request->modules) && is_array($request->modules)) {
            $item->modules()->sync($request->modules);
        }
        if(isset($request->link_types) && is_array($request->link_types)) {
            $item->link_types()->sync($request->link_types);
        }
        if(isset($request->sources) && is_array($request->sources)) {
            $item->sources()->sync($request->sources);
        }

        // HRequest::update_media($request->site_logos, $item, 'site_logos');
        if ($request->logo && count($request->logo)) {
            if(!$request->logo['id']) {
                $item->clearMediaCollection('site_logos');
            }
            HRequest::save_poster($item, $request->logo, 'site_logos');
        } else {
            $item->clearMediaCollection('site_logos');
        }

        return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);

    }

    public function pos_update(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'pos_surveys' => 'integer|nullable',
            'pos_variant_id' => 'integer|nullable',
            'pos_appeal_title' => 'string|max:500|nullable',
            'pos_appeal_code' => 'required|integer',
            'pos_appeal_description' => 'string|max:255|nullable',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }
        $item->pos_surveys = $request->pos_surveys;
        if(isset($request->pos_appeal_code)) {
            if (empty($request->pos_appeal_code) && !empty($request->pos_appeal_id)) {
                $pos_appeal = PosAppeal::find($request->pos_appeal_id);
                $pos_appeal->delete();
                $item->pos_appeal_id = null;
            } else if (!empty($request->pos_appeal_code) && empty($request->pos_appeal_id)) {
                $pos_appeal = new PosAppeal;
                $pos_appeal->title = $request->pos_appeal_title;
                $pos_appeal->code = $request->pos_appeal_code;
                $pos_appeal->description = $request->pos_appeal_description;
                $pos_appeal->pos_variant_id = $request->pos_appeal_variant_id;
                $pos_appeal->site_id = $id;
                $pos_appeal->save();
                $item->pos_appeal_id = $pos_appeal->id;
            } else {
                $pos_appeal = PosAppeal::find($request->pos_appeal_id);
                $pos_appeal->title = $request->pos_appeal_title;
                $pos_appeal->code = $request->pos_appeal_code;
                $pos_appeal->description = $request->pos_appeal_description;
                $pos_appeal->pos_variant_id = $request->pos_appeal_variant_id;
                $pos_appeal->save();
            }
        }
        $item->update();

        return ApiResponse::onSuccess(200, $this->messages['update_pos'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);

    }

    public function privacy_policy_update(Request $request, $id) {
        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->privacy_policy = $request->privacy_policy;
        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['update_policy'], $data = [
            'id' => $item->id,
            'title' => $item->title,
        ]);
    }

}