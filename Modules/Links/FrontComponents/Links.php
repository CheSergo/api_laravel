<?php

namespace App\Modules\Links\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

// Models
use App\Modules\Links\LinkTypes\LinkType;
use App\Modules\Links\Link;

class Links extends Controller
{
    public $model = Link::class;

    public function byType(Request $request) {
        $items = (object) $this->model::thisSiteFront()->published()
            ->whereHas('type', function($query) use ($request) {
                $query->where('code', $request->type);
            })
            ->with('media')
            ->orderBy('sort', 'ASC')->get()->append('color');
        
        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    public function useful() {
        $items = (object) LinkType::published()
            ->whereIn('code', ['federal', 'regional', 'current'])
            ->whereHas('links', function($query) {
                $query->thisSiteFront()->published();
            })
            ->with('links', function($query) {
                $query->thisSiteFront()->published()->orderBy('sort', 'ASC')
                ->select('title', 'description', 'redirect', 'type_id')
                ->with('section:title,slug,parent_id,path')->with('media');
            })->select('id', 'code', 'title')
            ->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->get();
        
        return [
            'meta' => [],
            'data' => $items,
        ];
    }

}