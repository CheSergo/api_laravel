<?php
namespace App\Modules\Birth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Helpers
use Carbon\Carbon;

// Models
use App\Modules\Birth\ConfigController;
use App\Modules\Commissions\Commission;
use App\Modules\Sections\Section;
use App\Jobs\CommissionBuilder;

use Exception;

class CommissionController extends Controller {

    public function commissions(Request $request) {
        CommissionBuilder::dispatch($request->site_id, $request->type, $request->config);
    }

    function createCommission($commission, $site_id) {
        $model = Commission::create([
            'title' => $commission->title,
            'slug' => $commission->slug,
            'site_id' => $site_id,
            'creator_id' => 0,
            'editor_id' => 0,
            'is_published' => 1,
            'created_at' => Carbon::now(),
            'published_at' => Carbon::now(),
        ]);

        if (isset($commission->section) && $commission->section) {
            $section = Section::where('title', $commission->section->section_title)->where('site_id', $site_id)->first();//????
            if ($section && !is_null($section)) {
                $redirect = $commission->section->redirect;
                $redirect->link = $model->id;
                $section->reroute = $commission->section->reroute;
                $section->redirect = $redirect;
                $section->save();
            }
        }
    }

    public function giveBirthToCommissions($site_id, $type, $config) {

        $configController = new ConfigController();
        $config = $configController->getConfig($type, $config);
        foreach($config as $commission) {
            $tmp = (array) $commission;
            if(!empty($tmp)) {
                $this->createCommission($commission, $site_id);
            }
        }
        return 'success';
    }
}