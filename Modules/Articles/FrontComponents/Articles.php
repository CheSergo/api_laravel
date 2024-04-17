<?php

namespace App\Modules\Articles\FrontComponents;

use App\Http\Controllers\Controller;

//Requests
use Illuminate\Http\Request;

// Filters
use App\Modules\Articles\ArticlesFilter;

// Models
use App\Modules\Sections\Section;
use App\Modules\Articles\Article;

// Helpers
use App\Helpers\Meta;

class Articles extends Controller {

    /**
     * @var ArticlesFilter
     */
    public $filter;
    public $model = Article::class;
    public $component = 'ArticlesList';

    /**
     * LinksController constructor.
     * @param ArticlesFilter $filter
     */
    public function __construct(ArticlesFilter $filter) {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index_old() {
        $items = (object) $this->model::filter($this->filter)->published()->orderBy('created_at', 'desc')->thisSiteFront()
            ->with('media')->paginate(12)->toArray();

        if(isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }
        
        return [
            'meta' => $meta,
            'data' => $items->data,
        ];
    }

    public function index() {
        $builder = (object) $this->model::filter($this->filter)->published()->orderBy('created_at', 'desc')->thisSiteFront()
            ->with('media')
            ->with('tags')
            ->with('categories')
            ->with('sources')
            // ->with('directions', function($q) {
            //     $q->whereNull('parent_id');
            // });
            ->with('direction_types');

        $for_meta = (object) $builder->get();
        $items = (object) $builder->filter($this->filter)->paginate(12)->toArray();

        $unique_sources = Meta::processItems($for_meta->pluck('sources')->toArray(), 'id', ['id', 'title']);
        $unique_tags = Meta::processItems($for_meta->pluck('tags')->toArray(), 'id', ['id', 'title', 'code']);
        $unique_directions = Meta::processItems($for_meta->pluck('direction_types')->toArray(), 'id', ['id', 'title']);
        $unique_categories = Meta::processItems($for_meta->pluck('categories')->toArray(), 'id', ['id', 'title', 'code']);

        Meta::sorting_by_title($unique_sources);
        Meta::sorting_by_title($unique_tags);
        Meta::sorting_by_title($unique_directions);
        Meta::sorting_by_title($unique_categories);

        if(isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        $meta['sources'] = $unique_sources;
        $meta['tags'] = $unique_tags;
        $meta['direction_types'] = $unique_directions;
        $meta['categories'] = $unique_categories;
        
        return [
            'meta' => $meta,
            'data' => $items->data,
        ];
    }

    public function slides($limit = 3) {
        return $this->model::published()->thisSiteFront()->orderBy('created_at', 'desc')
        ->whereHas('media', function($query) {
            $query->where('collection_name', 'article_posters');
        })->limit($limit)->select('id')->get()->pluck('id');
    }

    public function slider(Request $request) {
        $limit = $request->limit ?? 3;

        $section = Section::thisSiteFront()->published()->component($this->component)->first();

        $articles = (object) $this->model::published()->thisSiteFront()->orderBy('created_at', 'desc')
            ->with('media')->with('categories')->whereIn('id', $this->slides($limit))->get();

        
        return [
            'meta' => [
                'section' => $section,
            ],
            'data' => $articles,
        ];
    }

    public function list(Request $request) {
        $limit = $request->limit ?? 4;

        $section = Section::thisSiteFront()->published()->component($this->component)->first();

        $articles = (object) $this->model::published()->thisSiteFront()->orderBy('created_at', 'desc')
            ->with('media')->with('categories')->whereNotIn('id', $this->slides())
            ->limit($limit)->get();

        return [
            'meta' => [
                'section' => $section,
            ],
            'data' => $articles,
        ];
    }

    public function show(Request $request) {
        
        $article = (object) $this->model::thisSiteFront()->published()->where('slug', $request->slug)
        ->with('media')->with('workers')->with('direction_types')->with('documents', function($query) {
            $query->published();
        })->with('categories')->with('tags')->with('sources')->firstOrFail()->append('clips');

        return [
            'meta' => [],
            'data' => $article,
        ];
    }
}
