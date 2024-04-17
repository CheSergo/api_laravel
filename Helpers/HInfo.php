<?php
namespace App\Helpers;
use Illuminate\Support\Facades\App;
/**
 * Data class helper
 */
class HInfo {

    /**
     * @return object
     * Список месяцев
     */
    static function months()
    {
        return [1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',
            7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'];
    }
}