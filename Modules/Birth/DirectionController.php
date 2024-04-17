<?php
namespace App\Modules\Birth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

// Helpers
use Carbon\Carbon;
use App\Helpers\HString;

// Models
use App\Modules\Directions\Direction;
use App\Jobs\DirectionBuilder;
use App\Modules\Birth\ConfigController;

use Exception;

class DirectionController extends Controller
{
    public function directions(Request $request) {
        DirectionBuilder::dispatch($request->site_id, $request->type, $request->config);
    }

    function createDirection($direction, $parent_id, $site_id) {
        $model = Direction::create([
            'title' => $direction->title,
            'body' => isset($direction->body) && !is_null($direction->body) ? $direction->body : null,
            'slug' => strtolower(HString::transliterate($direction->title)),
            'redirect' => isset($direction->redirect) && !is_null($direction->redirect) ? $direction->redirect : null,
            'reroute' => isset($section->reroute) && !is_null($section->reroute) ? $section->reroute : null,
            'video' => isset($direction->video) && !is_null($direction->video) && count($direction->video) ? $direction->video : null,
            'sort' => isset($direction->sort) && !is_null($direction->sort) ? $direction->sort : 100,
            'type_id' => isset($direction->type_id) && !is_null($direction->type_id) ? $direction->type_id : null,
            'section_id' => null,
            'site_id' => $site_id,
            'creator_id' => 0,
            'editor_id' => 0,
            'parent_id' => $parent_id,
            'is_published' => 1,
            'views_count' => 0,
            'created_at' => Carbon::now(),
            'published_at' => Carbon::now(),
            'is_deleting_blocked' => isset($direction->is_deleting_blocked) && !is_null($direction->is_deleting_blocked) && $direction->is_deleting_blocked == 1? 1 : 0,
            'is_editing_blocked' => isset($direction->is_editing_blocked) && !is_null($direction->is_editing_blocked) && $direction->is_editing_blocked == 1 ? 1 : 0,
        ]);

        if(isset($direction->children) && count($direction->children)) {
            foreach($direction->children as $child) {
                $tmp = (array) $child;
                if(!empty($tmp)) {
                    $this->createDirection($child, $model->id, $site_id);
                }
            }
        }
    }

    public function giveBirthToDirections($site_id, $type, $config) {

        // $config = json_decode(file_get_contents(storage_path() . "/sitebirth//".$type."/".$config.".json"));
        $configController = new ConfigController();
        $config = $configController->getConfig($type, $config);
        foreach($config as $item) {
            $tmp = (array) $item;
            if(!empty($tmp)) {
                $this->createDirection($item, null, $site_id);
            }
        }
        return 'success';
    }

}