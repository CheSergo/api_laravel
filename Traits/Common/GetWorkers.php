<?php
namespace App\Traits\Common;

use App\Models\Common\Worker;
use Illuminate\Http\Request;

/**
 * Trait GetWorkers
 * @package App\Traits\Common
 * Получение персон
 */
trait GetWorkers {

    /**
     * @param Request $request
     * @return mixed
     */
    private function getWorkers(Request $request)
    {
        $workers = Worker::published()->with('media'); //filter($filter)-> WorkerFilter почему-то не работает тут
        $sites_astrobl = \HFunctions::getPermissionSite(['site-astrobl', 'site-iogv'], app('site_permissions'));

        //$workers->SiteAndGovWorkers(app('site_user')->site_id)
        $workers = $sites_astrobl ? $workers : $workers->thisSite(app('site_user')->site_id);
        if ($search_worker = $request->search_worker)
            $workers = $workers->searchNameWorkers($search_worker);

        return $workers->orderBy('surname', 'ASC')->paginate(10);
    }

    /**
     * @return mixed
     */
    private function getAllWorkers()
    {
        $workers = Worker::published()->with('media');
        $sites_astrobl = \HFunctions::getPermissionSite(['site-astrobl', 'site-iogv'], app('site_permissions'));
        $workers = $sites_astrobl ? $workers : $workers->thisSite(app('site_user')->site_id);
        return $workers->orderBy('surname', 'ASC')->get();
    }

    /**
     * @return mixed
     * Список персон сайта
     */
    private function getSiteWorkers() {
        return Worker::published()->thisSite(app('site_user')->site_id)->with('media')->orderBy('surname', 'ASC')->get();
    }

    /**
     * @return mixed
     * Список членов правительства
     */
    private function getGovWorkers() {
        return Worker::published()->govWorkers()->with('media')->orderBy('surname', 'ASC')->get();
    }

}