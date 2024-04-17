<?php
namespace App\Traits\Actions;

use Illuminate\Http\Request;

/**
 * Trait Resource
 * @package App\Traits\Actions
 * Основные методы для работы с ресурсом
 * В контроллере обязательно должно быть объявлено свойство public $model = 'Путь\До\Модели';
 */
trait Resource {

    /**
     * @param $request
     * @return mixed
     * Публикация и снятие с публикации ресурса
     */
    public function public(Request $request) {
        if(!$request->id) {return 'false';}
        return "test";
        //dd('safds');
        $item = $this->model::findOrFail($request->id);

        if ($item->is_published) {
            $item->is_published = false;
            $item->published_at = null;
        } else {
            $item->is_published = true;
            $item->published_at = now(); //"Y-m-d H:i:s"
        }
        $item->update();

        return $item->is_published;
    }

    /**
     * @param $request
     * @return mixed
     * Удаление ресурса
     */
    public function delete(Request $request) {
        
        if(!$request->id) {return;}
        $item = $this->model::findOrFail($request->id);

        $item->deleted_at = now();
        $item->update();

        return $item->deleted_at ? true : false;
    }

    /**
     * @param $request
     * @return mixed
     * Восстановление ресурса
     */
    public function restore(Request $request) {
        if(!$request->id) {return;}
        $item = $this->model::onlyTrashed()->findOrFail($request->id);

        $item->deleted_at = null;
        $item->update();

        return $item->deleted_at ? false : true;
    }

    /**
     * @param $request
     * @return mixed
     * Количество удалённых ресурсов
     */
    public function trash_count(Request $request) {
        return [
            'count' => $this->model::onlyTrashed()->count(),
        ];
    }

}