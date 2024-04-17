<?php
namespace App\Modules\Birth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Helpers
use Carbon\Carbon;
use App\Helpers\HString;

// Models
use App\Modules\Birth\ConfigController;
use App\Modules\Sections\Section;
use App\Jobs\SectionBuilder;

use Exception;

class SectionController extends Controller {

    public function sections(Request $request) {
        SectionBuilder::dispatch($request->site_id, $request->type, $request->config);
    }

    function createSection($section, $parent_id, $site_id) {
        $model = Section::create([
            'title' => $section->title,
            'body' => isset($section->body) && !is_null($section->body) ? $section->body : null,
            'slug' => isset($section->slug) && !is_null($section->slug) ? $section->slug : strtolower(HString::transliterate($section->title)),
            'redirect' => isset($section->redirect) && !is_null($section->redirect) ? $section->redirect : null,
            'reroute' => isset($section->reroute) && !is_null($section->reroute) ? $section->reroute : null,
            'video' => isset($section->video) && !is_null($section->video) && count($section->video) ? $section->video : null,
            'sort' => isset($section->sort) && !is_null($section->sort) ? $section->sort : 100,
            'is_deleting_blocked' => isset($section->is_deleting_blocked) && !is_null($section->is_deleting_blocked) && $section->is_deleting_blocked == 1 ? 1 : 0,
            'is_editing_blocked' => isset($section->is_editing_blocked) && !is_null($section->is_editing_blocked) && $section->is_editing_blocked == 1 ? 1 : 0,
            'site_id' => $site_id,
            'creator_id' => 0,
            'editor_id' => 0,
            'parent_id' => $parent_id,
            'is_show' => 1,
            'is_published' => 1,
            'created_at' => Carbon::now(),
            'published_at' => Carbon::now(),
        ]);

        if(isset($section->children) && count($section->children)) {
            foreach($section->children as $child) {
                $tmp = (array) $child;
                if(!empty($tmp)) {
                    $this->createSection($child, $model->id, $site_id);
                }
            }
        }
    }

    public function giveBirthToSections($site_id, $type, $config) {

        // $config = json_decode(file_get_contents(storage_path() . "/sitebirth//".$type."/".$config.".json"));
        $configController = new ConfigController();
        $config = $configController->getConfig($type, $config);
        foreach($config as $section) {
            $tmp = (array) $section;
            if(!empty($tmp)) {
                $this->createSection($section, null, $site_id);
            }
        }
        return 'success';
    }
}