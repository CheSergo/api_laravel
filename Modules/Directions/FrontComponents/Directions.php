<?php

namespace App\Modules\Directions\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

// Models
use App\Modules\Directions\Direction;
use App\Modules\Directions\DirectionTypes\DirectionType;

class Directions extends Controller 
{
    public $model = Direction::class;

    /**
     * @return mixed
     */
    public function list() {
        $items = (object) DirectionType::whereHas('directions', function($q) {
            $q->thisSiteFront()->published();
        })->select(['id', 'title', 'code'])->with('media')->orderBy('title', 'ASC')->get();

        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    /**
     * @return mixed
     */
    public function show($type, $direction) {

        if(!$type) {
            abort(404);
        }

        $type = (object) DirectionType::where('code', $type)->published()
        ->select(['id','title','code'])
        ->whereHas('directions', function($q) use ($direction) {
            $q->where('slug', $direction)->thisSiteFront()->published();
        })->with('media')->with('directions', function($qe) use ($direction) {
            $qe->thisSiteFront()->published()->where('slug', $direction)->with('documents', function($doc_q) {
                $doc_q->without('editor')->without('creator')->published();
            })->with('section')->with('childs')->with('parent')->with('media');
        })->firstOrFail();

        $direction = $type['directions'][0]->append('clips');
        unset($type['directions']);

        return [
            'direction' => $direction,
            'type' => $type,
        ];

    }

    /**
     * @return mixed
     */
    public function showByType($type) {

        if(!$type) {
            abort(404);
        }

        $item = (object) DirectionType::where('code', $type)->published()
        ->select(['id','title','code'])
        ->whereHas('directions', function($q) {
            $q->thisSiteFront()->published()->where('parent_id', null);
        })->with('directions', function($qe) {
            $qe->thisSiteFront()->published()->where('parent_id', null)->with('section');
        })->with('media')->firstOrFail();

        return $item;

        return [
            'meta' => [],
            'data' => $item,
        ];

    }
}
