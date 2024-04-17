<?php

namespace App\Helpers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiResponse {

    static function onSuccess(int $code = 200, string $message = '', $data = null, $meta = null): JsonResponse
    {
        if ($data) {
            return response()->json([
                'status'    => $code,
                'message'   => $message,
                'meta'      => $meta,
                'data'      => $data,
            ], $code);
        } else {
            return response()->json([
                'status'    => $code,
                'message'   => $message,
                'meta'      => $meta,
            ], $code);
        }
    }

    static function onError(int $code, string $messsage = '', $errors = null, $meta = null): JsonResponse 
    {
        return response()->json([
            'status'    => $code,
            'message'   => $messsage,
            'meta'      => $meta,
            'errors'    => $errors,
        ], $code);
    }

    static function validateException(int $code, string $messsage = '', $errors = null)
    {
        throw new HttpResponseException(response()->json([
            'status'    => $code,
            'message'   => $messsage,
            'errors'    => $errors
        ]));
    }

    static function editOrDelete($model, $id, $message, $type)
    {
        if($item = $model::find($id)) {
            if ($type == 'delete') {
                $item->delete();
                return ApiResponse::onSuccess(200, $message, $data = []);
            } else {
                return ApiResponse::onSuccess(200, $message, $data = $item);
            }
        } else {
            return ApiResponse::onError(404, 'Элемент не найден', $data = []);
        };
        
    }

}