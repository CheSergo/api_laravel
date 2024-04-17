<?php

namespace App\Modules\Sites\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

//Models
use App\Modules\Sites\Site;

class Sites extends Controller
{
    public $model = Site::class;

    public function data() {
        $item = (object) $this->model::where('id', request()->header('SiteID'))
            ->with('link_types')->with('contracts')->with('contract')
            ->with('pos_appeal')->with('social_networks')->with('media')
            ->first();

        return [
            'meta' => [],
            'data' => $item,
        ];
    }

    public function getSite(Request $request) {
        $site = (object) $this->model::whereIn('domain',
            [
                $request->site, 
                str_replace('dev.', '', $request->site), 
            ])
            ->with('link_types')
            ->with('pos_appeal')
            ->with('social_networks')
            ->with('media')
            ->with('modules:id,title,code')
            ->firstOrFail();

        return [
            'id' => $site->id,
            'site' => $site,
        ];
    }

    
}