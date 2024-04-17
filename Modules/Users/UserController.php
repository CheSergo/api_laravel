<?php

namespace App\Modules\Users;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

// Requests
use Illuminate\Http\Request;
use App\Modules\Users\UserRequest;

// Filters
use App\Modules\Users\UsersFilter;

// Models
use App\Modules\Sites\Site;
use App\Models\User;

// Helpers
use Hash;
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;
use App\Helpers\User\TokenHelper;

// Traits
use App\Traits\Actions\ActionMethods;

class UserController extends Controller {

    use ActionMethods;

    public $filter;
    public $model = User::class;
    private $site_id;

    protected $customMessages = [
        'name' => "Поле имя обязательно для заполнения",
        'surname' => 'Поле фамилия обязательно для заполнения',
        'name' => 'Поле имя обязательно для заполнения',
        'sites.required' => 'Выберите сайт или сайты которые будут доступны пользователю',
        'sites.array' => 'Ошибка формата поля сайты',
        'roles.required' => 'Выберите роль пользователя',
        'sites.array' => 'Ошибка формата поля роли',
        'abilities' => 'Ошибка формата поля доступы',
    ];

    public $messages = [
        'create' => 'Пользователь успешно добавлен',
        'edit' => 'Редактирование пользователя',
        'update' => 'Пользователь успешно изменен',
        'delete' => 'Пользователь успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * UserController constructor.
     * @param UsersFilter $filter
     */
    public function __construct(UsersFilter $filter) {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index() {

        $items = (object) $this->model::filter($this->filter)
        ->orderBy('created_at', 'desc')->with('sites:id,title,domain')
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

    public function getUser(Request $request)
    {
        $req_user = $request->user();
        $user = (object) $this->model::where('id', $req_user->id)
        ->with('roles', function($q) use ($req_user) {
            $q->where('site_id', $req_user->active_site_id);
        })
        ->with('active_site', function($q) {
            $q->with('contract')->with('modules');
        })
        ->first();
        // dd($user);
        // $user->update([
            // "last_active_at" => Carbon::now(),
        // ]);
        $user->last_active_at = Carbon::now();
        $user->timestamps = false;
        $user->save();

        $abilities = [];
        foreach ($user['roles'] as $role) {
            foreach($role->abilities()->get() as $ability) {
                array_push($abilities, $ability);
            };
        }
        $user['abilities'] = $abilities;

        // Обновление веремени жизни токена
        TokenHelper::token_expires($user);

        return $user;
    }

    public function getTokenAsAdmin(Request $request) {

        if(isset($request->user_id) && !is_null($request->user_id) && is_numeric($request->user_id)) {
            $user = (object) $this->model::where('id', $request->user_id)->first();
        }
        return $user->remember_token;
    }

    public function deleteTokens(Request $request) {

        $id = $request->user()->id;
        $user = (object) $this->model::where('id', $id)->first();

        $user->tokens()->delete();

        return 'deleted';
    }

    public function test() {
        $user = (object) $this->model::latest()->with('sites')->with('roles')->with('abilities')->first();

        return $user;
    }

    public function store(UserRequest $request) {

        $user = new User;

        $user->name = $request->name;
        $user->surname = $request->surname;
        $user->second_name = $request->second_name;
        // $user->password = Hash::make($request->password);
        $user->password = Hash::make($request->email);
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->position = $request->position;
        $user->department = $request->department;
        $user->comment = $request->comment;
        $user->password_changed_at = Carbon::now()->toDateTimeString();
        $user->is_blocked = !empty($request->is_blocked) ? 1 : 0;

        $user->save();

        if ($request->sources && count($request->sources)) {
            $user->sources()->attach($request->sources);
        }

        if (isset($request->sites) && count($request->sites)) {
            $sites_to_add = [];

            foreach($request->sites as $site) {
                $roles_to_add = $site['roles'];
                $user->roles()->attach($roles_to_add, ['site_id' => $site["id"]]);
                array_push($sites_to_add, $site["id"]);
            }

            foreach($sites_to_add as $site_id) {
                $site = Site::find($site_id);
                if($site) {
                    $user->sites()->attach($site);
                }
            } 
        } 

        $user->refresh();
        $sites_have = $user->sites->pluck('id')->toArray();
        if (!in_array($user->active_site_id, $sites_have)) {
            if (count($sites_have)) {
                $user->update([
                    'active_site_id' => $sites_have[0],
                ]);
            } else {
                $user->update([
                    'active_site_id' => 0,
                ]);
            }
        }

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = [$user]);
    }

    public function edit($id) {
        $user = (object) $this->model::with(['roles' => function ($q) {
            $q->withPivot('site_id');
        }])->with('sites')->with('sources')->findOrFail($id);

        $roles = $user['roles'];
        $sites = $user['sites'];

        unset($user["roles"]);
        unset($user["sites"]);

        $user['sites'] = collect();

        foreach ($sites as $site) {
            $site['roles'] = collect();
            foreach ($roles as $role) {
                if($role->pivot->site_id == $site->id) {
                    $site->roles->push($role);
                }
            }
            $user->sites->push($site);
        }

        $meta = [];
        return [
            'meta' => $meta,
            'data' => $user,
        ];
    }

    public function update(UserRequest $request, $id) {
        
        $user = $this->model::findOrFail($id);

        $user->name = $request->name;
        $user->surname = $request->surname;
        $user->second_name = $request->second_name;
        $user->password = Hash::make($request->email);
        $user->password_changed_at = Carbon::now()->toDateTimeString();
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->position = $request->position;
        $user->department = $request->department;
        $user->comment = $request->comment;
        $user->is_blocked = !empty($request->is_blocked) ? 1 : 0;

        $user->update();

        $user->sources()->sync($request->sources);

        if (isset($request->sites) && count($request->sites)) {
            $user->roles()->detach();
            $sites_to_sync = array();
            foreach($request->sites as $site) {
                array_push($sites_to_sync, $site['id']);
                $roles_to_add = $site['roles'];
                $user->roles()->attach($roles_to_add, ['site_id' => $site["id"]]);
            }
            $user->sites()->sync($sites_to_sync);
        } else {
            $user->roles()->detach();
            $user->sites()->detach();
        }

        $user->refresh();
        $sites_have = $user->sites->pluck('id')->toArray();

        if (!in_array($user->active_site_id, $sites_have)) {
            if (count($sites_have)) {
                $user->update([
                    'active_site_id' => $sites_have[0],
                ]);
            } else {
                $user->update([
                    'active_site_id' => 0,
                ]);
            }
        }

        return ApiResponse::onSuccess(200, $this->messages['update'], $user);
    }

    public function list(){
        $users = (object) $this->model::orderBy('created_at', 'desc')->paginate(10)->toArray();
        if (isset($users->path)) {
            $meta = [
                'path'          => $users->path,
                'to'            => $users->to,
                'from'          => $users->from,
                'total'         => $users->total,
                'current_page'  => $users->current_page,
                'per_page'      => $users->per_page,
                'last_page'     => $users->last_page,
            ];
        } else {
            $meta = [];
        }

        return [
            'meta' => $meta,
            'data' => $users->data,
        ];
    }

    public function alist(){
        $users = (object) $this->model::select(['id', 'name', 'surname', 'second_name'])->where("is_blocked", 0)->orderBy('created_at', 'desc')->get();
        $mappedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->surname . ' ' . $user->name . ' ' . $user->second_name,
            ];
        });

        return [
            'data' => $mappedUsers,
        ];
    }

    public function changeSite(Request $request) {
        $request->validate([
            'site_id' => 'required|int',
        ]);

        $id = $request->user()->id;
        $user = (object) $this->model::find($id);

        if (count($user->sites->whereIn('id', $request->site_id))) {
            $request->user()->tokens()->delete();

            $user->active_site_id = $request->site_id;
            $user->save();

            $token = $user->createToken($user->email);

            // Обновление веремени жизни токена
            TokenHelper::token_expires($user);

            // Возврат данных в ответе
            $data = [
                'token' => $token->plainTextToken,
            ];

            return ApiResponse::onSuccess(200, 'Сайт успешно изменен', $data);

        } else {
            return ApiResponse::onError(401, 'not restricted');
        }

    }

}