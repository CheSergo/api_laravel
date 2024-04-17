<?php
namespace App\Traits\Actions;

// use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\Api\ApiResponse;

use App\Modules\Logs\Test;

trait ActionMethods {

    public function publish($id) {
        if($item = $this->model::find($id)) {
            if($item->is_published == true) {
                $item->is_published = 0;
                $item->save();
                $message = 'Элеменет снят с публикации';
            } else {
                $item->is_published = 1;
                $item->published_at = Carbon::now();
                $item->save();
                $message = 'Элеменет опубликован';
            }

            return ApiResponse::onSuccess(200, $message, $data = $item);
        } else {
            return ApiResponse::onError(404, 'Элемент не найден', $errors = ['id' => 'not Found',]);
        }
    }

    public function delete($id)
    {
        if($item = $this->model::find($id)) {
            $item->delete();
            if (method_exists($item, 'saveLog')) {
                $item->saveLog($item->editor_id, $item, $item->getAttributes(), 'deleted');
            }
            return ApiResponse::onSuccess(200, $this->messages['delete']);
        } else {
            return ApiResponse::onError(404, 'Элемент не найден');
        }
    }

}