<?php

namespace App\Modules\Institutions\FrontComponents;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

//use App\Modules\Institutions\InstitutionsFilter;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Helpers

// Models
use App\Modules\Institutions\Institution;
use App\Modules\Sections\Section;

// Facades
//use Illuminate\Support\Facades\DB;

class Institutions extends Controller
{
    use ActionMethods, ActionsSaveEditItem;

    public $model = Institution::class;
    public $component = 'Institutions';

    public function index(Request $request)
    {
        // $items_old = (object) $this->model::thisSiteFront()->published()->orderBy('sort', 'ASC') // filter($this->filter)->
        //     ->with('creator:id,name,surname,second_name,email,phone,position')
        //     ->with('editor:id,name,surname,second_name,email,phone,position')
        //     ->withCount([
        //         'documents' => function ($query) {
        //             $query->published();
        //         }
        //     ])
        //     ->withCount([
        //         'workers' => function ($query) {
        //             $query->published();
        //         }
        //     ])
        //     ->with('type')
        //     ->get();
        $items = $this->model::thisSiteFront()->published()->select('title', 'slug', 'about', 'type_id', 'address', 'bus_gov', 'phone', 'fax', 'email', 'site')->with('type')
            ->withCount(['documents' => fn($query) => $query->published()])
            ->withCount(['workers' => fn($query) => $query->published()])
            ->get();
        return [
            'meta' => [],
            'data' => $items,
        ];
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show(Request $request)
    {
        $institution = $this->model::thisSiteFront()->published()->where('slug', $request->slug)->with(['documents', 'type', 'workers'])->firstOrFail();

        // $slug_section = explode(',', $request->sections);
        // $section = Section::thisSiteFront()->published()->where('slug', end($slug_section))->component($this->component)->firstOrFail();
        // $section->append('breadcrumbs');

        return [
            'meta' => [
                // 'section' => $section,
                // 'breadcrumbs' => $section->breadcrumbs,
            ],
            'data' => $institution,
        ];
    }

}