<?php
namespace App\Traits\Common;

use Illuminate\Http\Request;

/**
 * Trait GetWorkers
 * @package App\Traits\Common
 * Получение персон
 */
trait GetUser {

    static function getUser() {
        $request = app(\Illuminate\Http\Request::class);
        return $request->user();
    }

}