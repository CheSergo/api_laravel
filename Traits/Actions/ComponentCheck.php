<?php
namespace App\Traits\Actions;

use Illuminate\Http\Request;
use App\Modules\Components\Component;

trait ComponentCheckTrait {

    public function checkComponentParameters(Request $request, $section) {
        $ids = [];
        foreach($section->body['blocks'] as $block) {
            if($block['type'] == 'components') {
                array_push($ids, $block['data']['id']);
            }
        }
        $components_parameters = Component::whereIn('id', $ids)->get()->pluck('parameter')->toArray();
        if(!in_array($request->type, $components_parameters)) {
            abort(404);
        }
    }
 }