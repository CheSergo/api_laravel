<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use App\Models\Common\Site;
use App\Models\User;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Route;

/**
 * Files helpers
 */
class HFunctions
{

    /**
     * Генератор ссылки по аналогии с route()
     * В случае отсутствия роута возвращает url '/', а не исключение
     * @param $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    static function routeIf(string $name, array $parameters = [], bool $absolute = true):string {
        $url = $absolute ? request()->url().'/' : '/';
        $route = Route::getRoutes()->getByName($name);
        if (! is_null($route)) {
            if (count($parameters))
                return $route;

            return $url.$route->uri;
        }
        return $url;
    }

    /**
     * Проверка разрешения для текущего сайта (Сайт выбранный в админке)
     * @param array $permissions - Разрешения для сайта
     * @param array $site_permissions - Текущие разрешения выбранного сайта
     * @return bool
     */
    static function getPermissionSite($permissions, $site_permissions)
    {
        $permissions_array = [];
        foreach ($site_permissions as $permission) {
            $permissions_array[] = $permission->name;
        }
        $intersection = array_intersect($permissions_array, $permissions);
        if (!empty($intersection)) {
            return true;
        }
    }

    /**
     * Проверка доступа к сайтам текущего пользователя
     * @param $user_sites
     * @param array $sites_id
     * @return bool
     */
    static function isSiteUser($user_sites, $sites_id = [])
    {
        if (!empty($user_sites) && !empty($sites_id)) {
            $sites_array = [];
            foreach ($user_sites as $site) {
                $sites_array[] = $site->id;
            }
            $intersection = array_intersect($sites_array, $sites_id);
            if (!empty($intersection)) {
                return true;
            }
        }
    }

    /**
     * Проверка роли|ролей текущего пользователя
     * @param array $user - массив данных пользователя
     * @param array $roles_code - массив искомых ролей по символьному коду
     * @param bool $site_id - искать по идентификатору сайта
     * @return bool
     */
    static function isRoleUser($user, $roles_code = [], $site_id = false)
    {
        if (!empty($roles_code) && count($roles_code) > 0) {
            $roles_array = [];
            if ($site_id) {
                foreach ($user->roles()->where('site_id', $user->active_site_id)->get() as $role) {
                    $roles_array[] = $role->code;
                }
            } else {
                foreach ($user->roles as $role) {
                    $roles_array[] = $role->code;
                }
            }
            $intersection = array_intersect($roles_array, $roles_code);
            if (!empty($intersection)) {
                return true;
            }
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    static function getUserById($id)
    {
        $user = User::find($id);
        return $user;
    }

    /**
     * Информация о сайте 
     * @param $id
     * @return mixed
     */
    static function getSiteInformation($id)
    {
        $site = Site::find($id);
        return $site;
    }

    static function vkApi($method, array $params)
    {
        $request_params = array_merge([
            'v' => '5.131',
            'access_token' => '9fb35aede99c18594a52485c9cd5037caa1471ec8a96fbe9fc987b1f13fcbfb47f91ced73e76aba2cf1b5',
            //'secure_token' => 'DRaWTi0u1LueUiosA5Pb'
        ], $params);
        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/'.$method.'?'. $get_params));
        return $result;
    }

    static function ruTubeApi($id)
    {
        $result = json_decode(file_get_contents('https://rutube.ru/api/video/'.$id));
        return $result;
    }

    static function getSizeImage(array $proportions, $length, $width, $epsilon = 0.5)
    {
        if (isset($proportions) && count($proportions)==2)
        {
            $ratio = $proportions[0] / $proportions[1];
            $ratioImage = $length / $width;

            if (abs($ratio-$ratioImage) <= $epsilon) return true;
            else return false;
        }

    }

    static function getProportion($length, $width)
    {
        $ratio = $length / $width;
        return round($ratio,2);
    }

    static function checkAuthLDAP($login, $password, $test = false)
    {
        $ldap = ldap_connect(config('auth.ldap.uri.host'))
        or new \Exception('Unable to connect to the LDAP server!');
        if (!$ldap)
            throw new \Exception('LDAT connect is fail!');

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap , LDAP_OPT_REFERRALS, 0);

        $ldap_bind = @ldap_bind($ldap, mb_strtolower($login), $password); // привязка к ldap-серверу
        if (!$ldap_bind) // проверка привязки
            return false;

        if ($test) {
            $filter = "(&(objectCategory=user)(objectClass=user)(userPrincipalName=$login))";
            $search = ldap_search($ldap, "OU=Government,DC=astrobl,DC=ru", $filter);
            if ($search)
                return self::parseDataUserEMTS(ldap_get_entries($ldap, $search));

            return null;
        }

        return true;
    }

    /**
     * @param $stdArray
     * @return mixed
     * Парсинг в нормальный вид данных пользователя
     */
    static function parseDataUserEMTS($stdArray)
    {
        $result = [];
        foreach ($stdArray as $key => $array) {

            unset($array['objectclass']);

            if (!empty($array['userprincipalname'][0])) {

                $login_array = explode("@", $array['userprincipalname'][0]);
                $login = mb_strtolower($login_array[0]);

                $result = [
                    $login => (object) [
                        'login' => $login,
                        'email' => $array['userprincipalname'][0],
                        'fio' => !empty($array['cn'][0]) ? $array['cn'][0] : null,
                        'phone' => !empty($array['telephonenumber'][0]) ? $array['telephonenumber'][0] : null,
                        'department' => !empty($array['department'][0]) ? $array['department'][0] : null,
                        'company' => !empty($array['company'][0]) ? $array['company'][0] : null,
                        'post' => !empty($array['title'][0]) ? $array['title'][0] : null
                    ]
                ];
            }
        }
        return collect($result)->first();
    }

    /**
     * @param $url
     * @return bool
     */
    static function check_service($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
            return false;

        $curlInit = curl_init($url);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curlInit);
        curl_close($curlInit);

        if ($response)
            return true;
        return false;
    }


    /**
     * @param $url
     * @param int $timeout
     * @param string $type
     * @param array $data
     * @return bool|\Illuminate\Http\Client\Response
     */
    static function check_url($url, $timeout = 1, $type = 'GET', $data = [])
    {
        $response = false;
        try {
            switch($type) {
                case 'GET':
                    $response = Http::timeout($timeout)->get($url, $data);
                    $response = $response->ok();
                    break;
                case 'POST':
                    $response = Http::timeout($timeout)->post($url, $data);
                    $response = $response->ok();
                    break;
            }
            return $response;
        } catch (ConnectionException $e) {
            return $response;
        }
    }

}