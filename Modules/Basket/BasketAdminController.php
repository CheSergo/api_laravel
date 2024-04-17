<?php

namespace App\Modules\Basket;

use App\Http\Controllers\Controller;

// Requests
use Illuminate\Http\Request;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

// Filters
use App\Modules\Basket\BasketFilter;

class BasketAdminController extends Controller
{

    private $model = '';
    public $filter;

    public $basketList = [
        [
            'code'  => 'articles',
            'title' => 'Новости',
            'model' => 'App\Modules\Articles\Article',
            'url'   => 'contents/articles',
        ],
        [
            'code'  => 'documents',
            'title' => 'Документы',
            'model' => 'App\Modules\Documents\Document',
            'url'   => 'contents/documents',
        ],
        [
            'code'  => 'sections',
            'title' => 'Разделы',
            'model' => 'App\Modules\Sections\Section',
            'url'   => 'contents/sections',
        ],
        [
            'code'  => 'workers',
            'title' => 'Персоны',
            'model' => 'App\Modules\Workers\Worker',
            'url'   => 'contents/workers',
        ],
        [
            'code'  => 'departments',
            'title' => 'Структурные подразделения',
            'model' => 'App\Modules\Departments\Department',
            'url'   => 'contents/departments',
        ],
        [
            'code'  => 'directions',
            'title' => 'Направления деятельности',
            'model' => 'App\Modules\Directions\Direction',
            'url'   => 'contents/directions',
        ],
        [
            'code'  => 'links',
            'title' => 'Ссылки на главной',
            'model' => 'App\Modules\Links\Link',
            'url'   => 'contents/links',
        ],
        [
            'code'  => 'municipalities',
            'title' => 'Муниципальные образования',
            'model' => 'App\Modules\Municipalities\Municipalitie',
            'url'   => 'contents/municipalities',
        ],
        [
            'code'  => 'institutions',
            'title' => 'Подведомственные организации',
            'model' => 'App\Modules\Institutions\Institution',
            'url'   => 'contents/institutions',
        ],
        [
            'code'  => 'operational_informations',
            'title' => 'Оперативная информация',
            'model' => 'App\Modules\OperationalInformations\OperationalInformation',
            'url'   => 'contents/operational_informations',
        ],
        [
            'code'  => 'government_informations',
            'title' => 'Информация государственных структур',
            'model' => 'App\Modules\GovernmentInformations\GovernmentInformation',
            'url'   => 'contents/government_informations',
        ],
        [
            'code'  => 'information_systems',
            'title' => 'Информационные системы',
            'model' => 'App\Modules\InformationSystems\InformationSystem',
            'url'   => 'contents/information_systems',
        ],
        [
            'code'  => 'municipal_services',
            'title' => 'Реестр муниципальных услуг',
            'model' => 'App\Modules\MunicipalServices\MunicipalService',
            'url'   => 'contents/municipal_services',
        ],
        [
            'code'  => 'public_hearings',
            'title' => 'Публичные слушания',
            'model' => 'App\Modules\PublicHearings\PublicHearing',
            'url'   => 'contents/public_hearings',
        ],
        [
            'code'  => 'contests',
            'title' => 'Конкурсы',
            'model' => 'App\Modules\Contests\Contest',
            'url'   => 'contents/contests',
        ],
        [
            'code'  => 'smis',
            'title' => 'CМИ',
            'model' => 'App\Modules\Smis\Smi',
            'url'   => 'contents/smis',
        ],
    ];

    /**
     * BasketAdminController constructor.
     * @param BasketFilter $filter
     */
    public function __construct(BasketFilter $filter) {
        $this->filter = $filter;
    }

    private function getModel($code) {
        $trash_key = array_search($code, array_column($this->basketList, 'code'));
        $this->model = $this->basketList[$trash_key]['model'];
    }

    /**
     * @return mixed
     */
    public function trashList() {

        $items = [];

        foreach ($this->basketList as $basket) {
            $this->model = $basket['model'];
            $basket['trashList'] = (object) $this->model::onlyTrashed()->thisSite()->get();
            $items[] = $basket;
        }

        return [
            'data' => $items,
        ];
    }

    /**
     * @param $code
     * @return mixed
     */
    public function trashContent(Request $request, $code) {

        $this->getModel($code);

        $items = (object) $this->model::filter($this->filter)->onlyTrashed()->thisSite()->with('creator')->paginate(10)->toArray();

        if (isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        $trash_key = array_search($code, array_column($this->basketList, 'code'));

        return [
            'meta'  => $meta,
            'data'  => $items->data,
            'label' => $this->basketList[$trash_key]['title']
        ];
    }

    public function trashDelete($type, $id) {

        $this->getModel($type);

        $status = false;
        $items = json_decode($id);

        if (gettype($items) == "array") {

            foreach ($items as $item) {
                $model = (object) $this->model::where('id', $item)->onlyTrashed()->first();
                $model->forceDelete();
            }
            $status = true;

        } else {
            $model = (object) $this->model::where('id', $items)->onlyTrashed()->first();
            $model->forceDelete();
            $status = true;
        }

        return [
            'status' => $status,
        ];
    }

    public function trashRestore($type, $id) {

        $this->getModel($type);

        $status = false;
        $items = json_decode($id);

        if (gettype($items) == "array") {

            foreach ($items as $item) {
                $model = (object) $this->model::where('id', $item)->onlyTrashed()->first();
                $model->restore();
            }
            $status = true;

        } else {
            $model = (object) $this->model::where('id', $items)->onlyTrashed()->first();
            $model->restore();
            $status = true;
        }

        return [
            'status' => $status,
        ];
    }
}