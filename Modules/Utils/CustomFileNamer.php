<?php

namespace App\Modules\Utils;

use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Support\FileNamer\FileNamer as FileNamer;

class CustomFileNamer extends FileNamer {

    //Этот класс переопределяет дефолтный класс Spatie Media Library
    //Здесь переписана только функция originalFileName

    public function originalFileName(string $fileName): string
    {
        $nstr = preg_replace("/[^a-zA-Z.0-9,а-яА-Я_№(\)\/-]/iu", '', $fileName);
        $nstr = preg_replace("/\-+/", '-', $nstr);
        $nstr = preg_replace("/\_+/", '_', $nstr);
        $nstr = preg_replace("/[()]+/", '_', $nstr);
        return pathinfo($nstr, PATHINFO_FILENAME);
    }

    public function conversionFileName(string $fileName, Conversion $conversion): string
    {
        $strippedFileName = pathinfo($fileName, PATHINFO_FILENAME);

        return "{$strippedFileName}-{$conversion->getName()}";
    }

    public function responsiveFileName(string $fileName): string
    {
        return pathinfo($fileName, PATHINFO_FILENAME);
    }

}