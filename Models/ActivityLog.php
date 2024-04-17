<?php

namespace App\Models\Common;
use Spatie\Activitylog\Models\Activity;
use App\Http\Filters\Common\Filterable;

/**
 * Class ActivityLog
 * @package App\Models\Common
 *
 */

/* навзание логов должны быть в единственном числе и совпадать с названием  таблицы без s. вместо дефиса  _ */

class ActivityLog extends Activity
{
    use Filterable;

    public function getDescription()
    {
        switch ($this->description) {
            case 'updated':
                return 'Обновлено';
                break;
            case 'created':
                return 'Создано';
                break;
            case 'deleted':
                return 'Удалено';
                break;
            case 'login':
                return 'Вход';
                break;
            case 'logout':
                return 'Выход';
                break;
        }
    }

    public function getCauser()
    {
        $class = $this->causer_type;
        if ($class == 'App\Models\User')
        {
            $user = $class::find($this->causer_id);
            if (isset($user)) return $user->author;
        }
        else return $this->causer_id;
    }

    public function getMaterial()
    {
        $class = $this->subject_type;
        if (class_exists($class)) {
            if (method_exists($class, 'withTrashed'))
                $item = $class::withTrashed()->find($this->subject_id);
            else $item = $class::find($this->subject_id);

            if (isset($item))
            {
                if ($class == 'App\Models\Common\Worker') return $item->fio;
                if ($class == 'App\Models\User') return "Пользователь (".$item->email.")";
                else if ($class == 'App\Models\Sites\Data\Set') return $item->date_created;
                else if (isset($item->title)) return $item->title;
                else if (isset($item->name)) return $item->name;
            }

        }
    }

    public function getSubjectTypeUrl ()
    {
        switch ($this->subject_type) {
            case 'App\Models\Common\Component':
                return ['name' => 'Компонент', 'route' => 'components'];
                break;
            case 'App\Models\Common\Article':
                return ['name' => 'Событие', 'route' => 'articles'];
                break;
            case 'App\Models\Common\Category':
                return ['name' => 'Категория новостей', 'route' => 'categories'];
                break;
            case 'App\Models\Common\Department':
                return ['name' => 'Структурное подразделение', 'route' => 'departments'];
                break;
            case 'App\Models\Common\Direction':
                return ['name' => 'Направление деятельности', 'route' => 'directions'];
                break;
            case 'App\Models\Common\Document':
                return ['name' => 'Документ', 'route' => 'documents'];
                break;
            case 'App\Models\Common\Instruction':
                return ['name' => 'Инструкция', 'route' => 'instructions'];
                break;
            case 'App\Models\Common\Link':
                return ['name' => 'Ссылка', 'route' => 'links'];
                break;
            case 'App\Models\Common\Institution':
                return ['name' => 'Подведомственное учреждение', 'route' => 'institutions'];
                break;
            case 'App\Models\Common\JobOpening':
                return ['name' => 'Вакансия', 'route' => 'job-openings'];
                break;
            case 'App\Models\Common\MainStructure':
                return ['name' => 'Ресурс', 'route' => 'main-structures'];
                break;
            case 'App\Models\Common\NpProject':
                return ['name' => 'Национальный проект', 'route' => 'np-projects'];
                break;
            case 'App\Models\Common\RegProject':
                return ['name' => 'Региональный проект', 'route' => 'reg-projects'];
                break;
            case 'App\Models\Common\RegProjectSite':
                return ['name' => 'Региональный проект сайта', 'route' => 'articles'];
                break;
            case 'App\Models\Common\Section':
                return ['name' => 'Раздел', 'route' => 'sections'];
                break;
            case 'App\Models\Common\Source':
                return ['name' => 'Источник', 'route' => 'sources'];
                break;
            case 'App\Models\Common\Theme':
                return ['name' => 'Тематика', 'route' => 'themes'];
                break;
            case 'App\Models\Common\TimeInterval':
                return ['name' => 'Временной интервал', 'route' => 'time-intervals'];
                break;
            case 'App\Models\Common\TypeDocument':
                return ['name' => 'Тип документа', 'route' => 'type-documents'];
                break;
            case 'App\Models\Common\TypeList':
                return ['name' => 'Тип ссылки', 'route' => 'type-lists'];
                break;
            case 'App\Models\Common\Worker':
                return ['name' => 'Персона', 'route' => 'workers'];
                break;
            case 'App\Models\Common\FaqTheme':
                return ['name' => 'Тема вопроса', 'route' => 'faq-themes'];
                break;
            case 'App\Models\User':
                return ['name' => 'Пользователь', 'route' => 'users'];
                break;
            case 'App\Models\Common\Project':
                return ['name' => 'Проект', 'route' => 'projects'];
                break;
            case 'App\Models\Sites\Data\OpenData':
                return ['name' => 'Открытые данные', 'route' => 'open-datas'];
                break;
            case 'App\Models\Common\Commission':
                return ['name' => 'Совещательный и координационный орган', 'route' => 'commissions'];
                break;
            case 'App\Models\Common\Intervention':
                return ['name' => 'Мероприятие', 'route' => 'interventions'];
                break;

        }
    }
}
