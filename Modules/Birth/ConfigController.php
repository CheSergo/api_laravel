<?php
namespace App\Modules\Birth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Helpers
use App\Helpers\Api\ApiResponse;

// Models
use Exception;

class ConfigController extends Controller
{

    private $path;

    public function __construct()
    {
        $this->path = "/app/system/presets//";
    }

    public function getConfig($type, $config) {

        try {
            $file = json_decode(file_get_contents(storage_path() . $this->path.$type."/".$config.".json"));
        }catch(Exception $ex){
            return null;
        }
        return $file;

    }

    public function saveConfig(Request $request) {
        (object) $json = json_decode($request->json);
        if(!$json) {
            abort(404);
        }

        try {
            file_put_contents(storage_path() . $this->path.$request->type."/".$request->config.".json", json_encode($json));
        }catch(Exception $ex){
            return ApiResponse::onSuccess(200, "Ошибка при сохранении пресета", $data = $json);;
        }
        return ApiResponse::onSuccess(200, "Пресет успешно изменен", $data = $json);
    }

}