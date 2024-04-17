<?php
namespace App\Helpers;
use Illuminate\Support\Facades\App;
/**
 * Data class helper
 */
class HData {

    /**
     * @return object
     * Переводчик
     */
    static function trans() {
        switch (App::getLocale()) {
            case 'ru':
                $months = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
                $months_adaptive = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
                $weekDays = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
                $today_at = 'Сегодня в ';
                $yesterday_at = 'Вчера в ';
                break;
            case 'en':
                $months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
                $months_adaptive = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
                $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $today_at = 'Today at ';
                $yesterday_at = 'Yesterday at ';
                break;
            case 'de':
                $months = ['january', 'februar', 'märz', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'october', 'november', 'dezember'];
                $months_adaptive = ['january', 'februar', 'märz', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'october', 'november', 'dezember'];
                $weekDays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
                $today_at = 'Heute in ';
                $yesterday_at = 'Gestern in ';
                break;
            case 'fr':
                $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                $months_adaptive = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                $weekDays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                $today_at = "Aujourd'hui en ";
                $yesterday_at = 'Hier à ';
                break;
            default:
                $months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
                $months_adaptive = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
                $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $today_at = 'Today at ';
                $yesterday_at = 'Yesterday at ';
        }
        return (object) [
            'weekDays' => $weekDays,
            'months' => $months,
            'months_adaptive' => $months_adaptive,
            'today_at' => $today_at,
            'yesterday_at' => $yesterday_at,
        ];
    }

    /**
     * @param $value
     * @param bool $show_time
     * @return string
     * @throws \Exception
     * Пример - 22 сентября 2021, 10:00
     */
    static function rus_data($value, $show_time = true)
    {
        $data = new \DateTime($value);
        $trans = self::trans();

        if($show_time == true)
            return $data->format('j') . ' ' . $trans->months_adaptive[$data->format('n') - 1] . ' ' . $data->format('Y') . ', ' . $data->format('G:i');

        return $data->format('j') . ' ' . $trans->months_adaptive[$data->format('n') - 1] . ' ' . $data->format('Y');
    }


    /**
     * @param $value
     * @return string
     * @throws \Exception
     * Дата как в социальных сетях
     * Примеры - Вторник, 9:01 / 22 сентября 2021, 10:00
     */
    static function social_data($value)
    {
        $time = strtotime($value);
        $data = new \DateTime($value);
        $trans = self::trans();

        if ($time > strtotime('today'))
        {
            return $trans->today_at . $data->format('G:i');
        }
        elseif ($time > strtotime('yesterday'))
        {
            return $trans->yesterday_at . $data->format('G:i');
        }
        elseif ($time > strtotime('this week'))
        {
            return $trans->weekDays[$data->format('N') - 1] . ', ' . $data->format('G:i');
        }
        else
        {
            return $data->format('j') . ' ' . $trans->months_adaptive[$data->format('n') - 1] . ' ' . $data->format('Y') . ', ' . $data->format('G:i');
        }
    }

    /**
     * @param $value
     * @return string
     * @throws \Exception
     * ГГГГ-ММ-ДД
     */
    static function small_format_date($value)
    {
        $date = new \DateTime($value);
        return $date->format('Y-m-d');
    }

    /**
     * @param $value
     * @return string
     * @throws \Exception
     * ГГГГ-ММ-ДД ЧЧ:ММ:СС
     */
    static function format_datetime($value)
    {
        $date = new \DateTime($value);
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param $value
     * @return string
     * @throws \Exception
     * Формат для датапикеров
     * ДД.ММ.ГГГГ
     */
    static function format_small_daterangepicker($value)
    {
        $date = new \DateTime($value);
        return $date->format('d.m.Y');
    }

    /**
     * @param $value
     * @return string
     * @throws \Exception
     * Формат для датапикеров
     * ДД.ММ.ГГГГ ЧЧ.ММ
     */
    static function format_daterangepicker($value)
    {
        $date = new \DateTime($value);
        return $date->format('d.m.Y H:i');
    }

    /**
     * @param $value
     * @return object
     * @throws \Exception
     * Разбивка даты по частям
     */
    static function partials($value) {
        $date = new \DateTime($value);
        $trans = self::trans();
        return (object) [
            'day' => $date->format('d'),
            'weekDay' => $trans->weekDays[$date->format('N') - 1],
            'month' => $trans->months[$date->format('n') - 1],
            'month_adaptive' => $trans->months_adaptive[$date->format('n') - 1],
            'year' => $date->format('Y'),
            'hours' => $date->format('H'),
            'minutes' => $date->format('i'),
            'time' => $date->format('H:i'),
        ];
    }

    /**
     * @return string
     * Текущее время года
     */
    static function current_season() {
        $months = date('m');
        switch ($months) {
            case 1: case 2: case 12:
                return 'winter';
                break;
            case 3: case 4: case 5:
                return 'spring';
                break;
            case 6: case 7: case 8:
                return 'summer';
                break;
            case 9: case 10: case 11:
                return 'autumn';
                break;
            default:
                throw new Exception($months.' - месяц. Быть такого не может!');
                break;
        }
    }
}