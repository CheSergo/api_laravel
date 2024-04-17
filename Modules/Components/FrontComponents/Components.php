<?php
namespace App\Modules\Components\FrontComponents;

use App\Http\Controllers\Controller;

// Models
use App\Modules\Components\Component;

class Components extends Controller 
{
    public $model = Component::class;

    public function first($id) {
        return Component::where('id', $id)->with('module')->first();
    }

}
