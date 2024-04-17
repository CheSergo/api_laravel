<?php
namespace App\Helpers;
use Illuminate\Support\Str;

/**
 * String helpers
*/
class HString {

    /**
     * @return string[]
     * Мусор для очистки в строках
     */
    static function replace() {
        return ['\r\n' => ' ', '\r' => ' ', '\n' => ' ',
            '&nbsp;' => '', 'nbsp;' => ' ', 'mdash;' => ' - ', 'laquo;' => '', 'raquo;' => '',
            '&amp;' => '', 'amp;' => '', 'quot;' => '"', '&quot;' => '"'];
    }

    /**
     * rus_quot()
     * Замена всех типов ковычек на русские ёлочки
     * @param $str
     *
     * @return string
     */
    static function rus_quot($str) {
        return preg_replace_callback(
            '#(([\"]{2,})|(?![^\W])(\"))|([^\s][\"]+(?![\w]))#u',
            function ($matches) {
                if (count($matches)===3) return "«»";
                else if ($matches[1]) return str_replace('"',"«",$matches[1]);
                else return str_replace('"',"»",$matches[4]);
            },
            str_replace("&quot;", '"', str_replace("'", '"', str_replace("”", '"', str_replace("“", '"', strtr($str, self::replace())))))
        );
    }

    static function uuid() {
        $abc = [
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r',
            's', 't', 'u', 'v', 'w', 'x', 'y', 'z'
        ];
        $slug = '';
        $exp = str_split(strtotime('now'));
        foreach ($exp as $key => $n) {
            $letter = $n % 2 == 0 ? $abc[$n] : '';
            $sep = $key == 3 || $key == 6 || $key == 8 ? '-' : '';
            $slug.=$sep.$n.$letter;
        }
        return $slug;
    }

    /**
     * @param $url
     * @return string
     * Определение цели ссылки
     */
    static function target($url) {
        $str = stripos($url, 'http');

        if ($str === false) {
            $target = '_self';
        } else {
            $target = '_blank';
        }

        return 'target='.$target;
    }


    /**
     * Формирование slug из заголовка (Сокращение до первых букв, удаление ковычек, конвертирование из кириллицы в латиницу)
     * @param $str
     * @return mixed
     */
    static function reduction($str) {
        $str = mb_strtolower(preg_replace('/[\«\»\'\"]/u', '', $str));
        $first_str = [];
        $arr_str = explode(' ', $str);
        foreach ($arr_str as $value) {
            $first_str[] = mb_substr($value, 0, 1);
        }
        return Str::slug(implode('', $first_str));
    }

    /**
     * Проверка наличия в строке знаков тире (-) и замена их на символы нижнее подчеркивание (_)
     * @param $str
     * @return string|string[]|null
     */
    static function dashToUnderlining($str) {
        $array_str = explode("-", $str);
        if (count($array_str) > 0) {
            return preg_replace('/-/', '_', $str);
        }
    }

    /**
     * Первые символы в верхний регистр и перевод строки в единственное число (убрать "s" в конце строки)
     * Проверка наличия в строке знаков тире (-), удаление их и перевод первого символа следущей строки в верхний регистр
     * @param $str
     * @return mixed
     */
    static function firstUppercaseAndSingular($str) {

        $result = null;
        //$array_str = explode("_", $str);
        $array_str = explode("-", $str);
        $array = [];

        if (count($array_str) > 0) {
            foreach ($array_str as $value) {
                $array[] = ucfirst($value);
                $result = implode("", $array);
            }
        } else {
            $result = ucfirst($str);
        }

        return substr($result, 0, -1);
    }

    /**
     * Транслитерация строки из киррилицы в латиницу
     * @param $string
     * @return string
     */
    static function transliterate($string) {
        $st = array(
            'а' => 'a',   'б' => 'b',   'в' => 'v',
            'г' => 'g',   'д' => 'd',   'е' => 'e',
            'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
            'и' => 'i',   'й' => 'y',   'к' => 'k',
            'л' => 'l',   'м' => 'm',   'н' => 'n',
            'о' => 'o',   'п' => 'p',   'р' => 'r',
            'с' => 's',   'т' => 't',   'у' => 'u',
            'ф' => 'f',   'х' => 'h',   'ц' => 'c',
            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
            'ь' => '',    'ы' => 'y',   'ъ' => '',
            'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

            'А' => 'A',   'Б' => 'B',   'В' => 'V',
            'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
            'И' => 'I',   'Й' => 'Y',   'К' => 'K',
            'Л' => 'L',   'М' => 'M',   'Н' => 'N',
            'О' => 'O',   'П' => 'P',   'Р' => 'R',
            'С' => 'S',   'Т' => 'T',   'У' => 'U',
            'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
            'Ь' => '',    'Ы' => 'Y',   'Ъ' => '',
            'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
            ' ' => '-',   '"' => '',    '№' => '',
            '.' => '',    ',' => '',    '«' => '',
            '»' => '',
        );
        return strtr($string, $st);
    }

    /**
     * @param $string
     * @param int $length
     * @return string|null
     * Чистка и обрезка текста для вывода краткого описания (по-умолчанию 255 символов)
     */
    static function descContent($string, $length = 255)
    {
        $desc = strtr(strip_tags($string), self::replace());
        $desc = rtrim(trim($desc));
        //$desc = preg_replace('#[^а-яЁА-ЯёA-Za-z;:0-9_.,№"«»!? -]+#u', '', $desc);
        if (iconv_strlen($desc) > $length) {
            $desc = mb_substr($desc, 0, $length);
            $desc = preg_replace("/\\s\\S+$/u", "", $desc); // удаляем последнее слово
            $desc .= ' ...';
        }

        return $desc ?? null;
    }

    /**
     * @param $value
     * Отчистка строки от мусора и замены первой буквы на заглавную
     */
    static function replaceSymbolsHashTag($value)
    {
        $replace = ['\r\n' => ' ', '\r' => ' ', '\n' => ' ', '\\' => '',
            '&nbsp;' => ' ', 'nbsp;' => ' ', 'mdash;' => ' - ', 'laquo;' => '', 'raquo;' => ''];
        $desc = strtr(strip_tags($value), $replace);
        $desc = preg_replace('#[^а-яЁА-ЯёA-Za-z;:0-9_.,"«»!? -]+#u', '', rtrim(trim($desc)));

        return $desc;
    }
}
