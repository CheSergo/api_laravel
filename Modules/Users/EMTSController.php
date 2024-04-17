<?php

namespace App\Modules\Users;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// Requests
use Illuminate\Http\Request;
use App\Modules\Users\UserRequest;

// Models
use App\Models\User;

// Helpers
use Carbon\Carbon;

class EMTSController extends Controller {

    public function index(Request $request)
    {
        $items = null;
        if (!empty($request->search)) {
            $users = Http::get('https://login.astrobl.ru/api/getSearchUsers', [
                'search' => $request->search,
            ])->json();

            foreach ($users as $key => $item) {
                $userEMTS = User::where('email', $item['email'])->first();
                if (!empty($userEMTS)) {
                    $users[$key]['web'] = true;
                } else {
                    $users[$key]['web'] = false;
                }
            }
            if (!empty($users)) {
                $items = $users;
            }

            $usersCollection = new Collection($users);

            // Paginate the collection
            $perPage = 10; // Number of items per page
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $paginatedUsers = $usersCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $paginatedUsersCollection = new Collection($paginatedUsers);
            $pagination = new LengthAwarePaginator(
                $paginatedUsersCollection,
                count($usersCollection),
                $perPage,
                $currentPage,
                ['path' => $request->url()]
            );
            $pagination = $pagination->jsonSerialize();

            $meta = [
                'path'          => $pagination['path'],
                'pagination' => [
                    'to'            => $pagination['to'],
                    'from'          => $pagination['from'],
                    'total'         => $pagination['total'],
                    'current_page'  => $pagination['current_page'],
                    'per_page'      => $pagination['per_page'],
                    'last_page'     => $pagination['last_page'],
                ],
            ];

        }

        if(!isset($meta)) {
            return [
                'meta' => [],
                'data' => $items,
            ];
        } else {
            return [
                'meta' => $meta,
                'data' => $paginatedUsersCollection->values(),
            ];
        }

    }

    /**
     * @param string $login
     * @return array
     */
    public function login(string $login)
    {
        $email = $login . "@astrobl.ru";
        $web = false;
        $user = null;
//        $allUsers = User::pluck("email")->toArray();
//        if (in_array($email, $allUsers)) {
//            $id = User::where("email", $email)->pluck("id")->first();
//            $web = true;
//            $user = $id;
//        } else {
            $user = Http::get("http://login.astrobl.ru/api/getUser", [
                "login" => $email,
            ])->json();
//        }

        return [
            "web" => $web,
            "data" => $user,
        ];
    }
}