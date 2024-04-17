<?php

namespace App\Modules\Utils;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VersionController extends Controller
{

    public static function parseVersiontxt() {
        $lines = file('/var/www/api/.version.txt');
        $last_line = end($lines);

        $pos = strrpos($last_line, "v");
        if ($pos !== false) {
            $result = substr($last_line, $pos + strlen('v'));
            return $result;
        }
    }
}
