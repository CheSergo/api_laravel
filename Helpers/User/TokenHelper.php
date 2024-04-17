<?php

namespace App\Helpers\User;

use Carbon\Carbon;

class TokenHelper {

    // Установка времени жизни токена
    static function token_expires($user)
    {
        $user->tokens()->first()->update([
            'expires_at' => Carbon::now()->addMinutes(env('TOKEN_LIFETIME'))->format('Y-m-d H:i:s'),
        ]);
    }

}