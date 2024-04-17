<?php

namespace App\Modules\Documents\FrontComponents;

use App\Http\Controllers\Controller;

// Requests
use Illuminate\Http\Request;

// Filters
use App\Modules\Documents\DocumentsFilter;

// Helpers
use App\Helpers\Meta;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Models
use App\Modules\Documents\Document;
use App\Modules\Documents\DocumentTypes\DocumentType;

class DocumentsProjects extends Controller 
{
    // проекты муниципальных правовых актов

    use ActionMethods, ActionsSaveEditItem;

    /**
     * @var DocumentsFilter
     */
    public $filter;
    public $model = Document::class;
    public $component = 'Documents';


    /**
     * DocumentsController constructor.
     * @param DocumentsFilter $filter
     */
    public function __construct(DocumentsFilter $filter) {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index() {
        // Допустимые типы документов
        $type_ids = [28, 41, 44];

        $builder = (object) $this->model::published()->orderBy('date', 'desc')->orderBy('numb', 'asc')
        ->thisSiteFront()
        ->where('is_mpa', true)
        ->whereIn('type_id', $type_ids)
        ->with('site')->with('media')->with('type')->with('interval')->with('tags');

        $for_meta = (object) $builder->get();
        $items = (object) $builder->filter($this->filter)->paginate(10)->toArray();

        $unique_dates = [];
        $unique_statuses = [];
        $unique_type_ids = [];
        $unique_sources = [];

        $dates = $for_meta->pluck('date')->toArray();
        $statuses = $for_meta->pluck('status')->toArray();
        $type_ids = $for_meta->pluck('type_id')->toArray();
        $sources = array_map(function($element) {
            if(count($element)) {
                return $element[0];
            }
        }, $for_meta->pluck('sources')->toArray());
        $sources = array_values(array_filter($sources, function($value) {
            return $value !== null;
        }));

        foreach ($dates as $date) {
            if(!is_null($date)) {
                $formattedDate = date("Y", strtotime($date));
                $unique_dates[$formattedDate] = true;
            }
        }

        foreach ($statuses as $status) {
            if (!is_null($status)) {
                $unique_statuses[$status] = true;
            }
        }

        foreach ($type_ids as $type_id) {
            if (!is_null($type_id)) {
                $unique_type_ids[$type_id] = true;
            }
        }

        foreach ($sources as $index => $source) {
            $s = [
                'id' => $source['id'],
                'title' => $source['title'],
            ];
            $unique_sources[$source['id']] = $s;
        }

        $unique_dates = array_keys($unique_dates);
        $unique_statuses = array_keys($unique_statuses);
        $unique_type_ids = array_keys($unique_type_ids);
        $unique_sources = array_values($unique_sources);

        $types = DocumentType::whereIn('id', $unique_type_ids)->select(['id','title'])->get();

        if(isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }
        $meta['years'] = $unique_dates;
        $meta['statuses'] = $unique_statuses;
        $meta['types'] = $types;
        $meta['sources'] = $unique_sources;

        return [
            'meta' => $meta,
            'data' => $items->data,
        ];
    }

    public function show(Request $request) {

        if(!$request->slug) {
            abort(404);
        }

        $item = (object) $this->model::thisSiteFront()
        ->published()
        ->where('slug', $request->slug)
        ->with('media')
        ->with('interval')
        ->with('commissions')
        ->with('directions')
        ->with('site')
        ->with('articles')
        ->firstOrFail();

        return [
            'meta' => [],
            'data' => $item,
        ];
    }

}
