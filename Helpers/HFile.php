<?php
namespace App\Helpers;

/**
 * Files helpers
 */
class HFile {

    static function getExtension($filename) {
        return substr($filename, strrpos($filename, '.') + 1);
    }

    /**
     * @param $mime_type
     * Получить расширение по mime_type
     */
    static function getExtByMimeType($mime_type) {

        $mime_types = array(

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
            '7z' => 'application/x-7z-compressed',
            'bz' => 'application/x-bzip',
            'bz2' => 'application/x-bzip2',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odp' => 'application/vnd.oasis.opendocument.presentation',

            //other
            'abw' => 'application/x-abiword',
            'arc' => 'application/x-freearc',
            'epub' => 'application/epub+zip',
        );
        $flipped = array_flip($mime_types);

    }

    /**
     * @param $ext
     * Получить иконку по расширению файла
     */
    static function getIconByExt($ext) {
        $exts = [
            'txt' => 'file-text', 'htm' => 'file-code', 'html' => 'file-code', 'php' => 'file-code',
            'css' => 'file-code', 'js' => 'file-code', 'json' => 'file-code', 'xml' => 'file-code', 'swf' => 'file-code',
            'csv' => 'file-csv',
            // images
            'png' => 'file-image', 'jpe' => 'file-image', 'jpeg' => 'file-image', 'jpg' => 'file-image', 'gif' => 'file-image',
            'bmp' => 'file-image', 'ico' => 'file-image', 'tiff' => 'file-image', 'tif' => 'file-image', 'svg' => 'file-image', 'svgz' => 'file-image',
            // archives
            'zip' => 'file-archive', '7z' => 'file-archive', 'rar' => 'file-zip', 'exe' => 'file', 'msi' => 'file', 'cab' => 'file',
            // audio/video
            'mp3' => 'file-audio', 'qt' => 'file-video', 'mov' => 'file-video', 'flv' => 'file-video',
            // adobe
            'pdf' => 'file-pdf', 'psd' => 'file-image', 'ai' => 'file-image', 'eps' => 'file-image', 'ps' => 'file-code',
            // ms office
            'doc' => 'file-word', 'docx' => 'file-word', 'rtf' => 'file-word', 'xls' => 'file-excel', 'xlsx' => 'file-excel', 'ppt' => 'file-powerpoint', 'pptx' => 'file-powerpoint',
            // open office
            'odt' => 'file-word', 'ods' => 'file-excel',
        ];
        if(isset($exts[$ext])){
            return $exts[$ext];
        } else {
            return 'file';
        }
    }

    static function getFileSizeFormat($filesize)
    {
        $formats = array('Б','КБ','МБ','ГБ','ТБ');// варианты размера файла
        $format = 0;// формат размера по-умолчанию

        // прогоняем цикл
        while ($filesize > 1024 && count($formats) != ++$format)
        {
            $filesize = round($filesize / 1024, 2);
        }

        // если число большое, мы выходим из цикла с
        // форматом превышающим максимальное значение
        // поэтому нужно добавить последний возможный
        // размер файла в массив еще раз
        $formats[] = 'ТБ';

        return $filesize.$formats[$format];
    }
}