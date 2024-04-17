<?php

namespace App\Modules\Articles;

use App\Http\Controllers\Controller;

// Requests
use Illuminate\Http\Request;
use App\Modules\Articles\ArticleRequest;

// Filters
use App\Modules\Articles\ArticlesFilter;

// Models
use App\Modules\Articles\Article;
use App\Modules\Logs\LogService;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class ArticleAdminController extends Controller 
{
    use ActionMethods, ActionsSaveEditItem;

    /**
     * @var ArticlesFilter
     */
    public $filter;
    public $model = Article::class;
    public $messages = [
        'create' => 'Новость успешно добавлена',
        'edit' => 'Редактирование новости',
        'update' => 'Новость успешно изменена',
        'delete' => 'Новость успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * ArticleAdminController constructor.
     * @param ArticlesFilter $filter
     */
    public function __construct(ArticlesFilter $filter) {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index(Request $request) {

        $builder = (object) $this->model::filter($this->filter)->orderBy('created_at', 'desc')->thisSite()
            ->with('site')
            ->with('media')
            ->with('workers')
            ->with('sources')
            ->with('tags')
            ->with('directions')
            ->with('categories')
            ->with('creator');

        $for_meta = (object) $builder->get();
        $items = (object) $builder->paginate(10)->toArray();

        $unique_sources = Meta::processItems($for_meta->pluck('sources')->toArray(), 'id', ['id', 'title']);
        $unique_tags = Meta::processItems($for_meta->pluck('tags')->toArray(), 'id', ['id', 'title', 'code']);
        $unique_directions = Meta::processItems($for_meta->pluck('directions')->toArray(), 'id', ['id', 'title']);
        $unique_categories = Meta::processItems($for_meta->pluck('categories')->toArray(), 'id', ['id', 'title']);
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
        $meta['directions'] = $unique_directions;
        $meta['categories'] = $unique_categories;

        return ApiResponse::onSuccess(200, 'success', $data = $items->data, $meta);
        
    }

    public function store(ArticleRequest $request) {

        $item = new $this->model;

        $item->title = $request->title;
      
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=false);

        $item->body = $request->body;
        $item->video = $request->video;

        $item->creator_id = $request->user()->id;
        $item->editor_id = $request->user()->id;

        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        if($request->is_pin) {
            $item->is_pin = 1;
            $item->pin_date = isset($request->pin_date) ? $request->pin_date : Carbon::now();
        } else {
            $item->is_pin = 0;
        }

        $item->is_portal = !empty($request->is_portal) ? 1 : 0;
        
        $item->save();

        $log_class = new LogService;
        $allAttributes = $item->getAttributes();
        $allAttributes['relations'] = [];
        $relations = [];

        // Приклепление медиа
        if($request->poster && is_array($request->poster)) {
            HRequest::save_poster($item, $request->poster, 'article_posters');
            $poster = $item->getMedia('article_posters')->first();
            if($poster) {
                $relations['media']['poster']['id'] = $poster?->id;
                $relations['media']['poster']['name'] = $poster?->name;
            }
        }

        if ($request->gallery && count($request->gallery)) {
            HRequest::save_gallery($item, $request->gallery, "article_gallery");
            $item->load('media');
            $gallery = $item->getMedia('article_gallery');
            if($gallery && count($gallery)) {
                foreach ($gallery as $k => $pic) {
                    $relations['media']['gallery'][$k]['id'] = $pic?->id;
                    $relations['media']['gallery'][$k]['name'] = $pic?->name;
                }
            }

        }

        // Прикрепленеие категорий
        if($request->categories && is_array($request->categories)) {
            $item->categories()->attach($request->categories);
            $relations['categories'] = $log_class->addRelationshipValues($item, 'categories');
        }

        // Прикрепленеие источников
        if($request->sources && is_array($request->sources)) {
            $item->sources()->attach($request->sources);
            $relations['sources'] = $log_class->addRelationshipValues($item, 'sources');
        }

        // Прикрепленеие направлений деятельности
        if($request->direction_types && is_array($request->direction_types)) {
            $item->direction_types()->attach($request->direction_types);
            $relations['direction_types'] = $log_class->addRelationshipValues($item, 'direction_types');
        }

        // Прикрепленеие документов
        if($request->documents && is_array($request->documents)) {
            foreach($request->documents as $index => $document) {
                $item->documents()->attach($document, ['document_sort' => $index+1]);
            }
            $relations['documents'] = $log_class->addRelationshipValues($item, 'documents');
        }

        // Прикрепленеие рабов
        if($request->workers && is_array($request->workers)) {
            foreach($request->workers as $index => $worker) {
                $item->workers()->attach($worker, ['worker_sort' => $index+1]);
            }
            $relations['workers'] = $log_class->addRelationshipValues($item, 'workers');
        }

        // Прикрепление тэгов
        if($request->tags && is_array($request->tags)) {
            $tags_array = HRequest::tags($request->tags);
            $item->tags()->attach($tags_array);
            $relations['tags'] = $log_class->addRelationshipValues($item, 'tags');
        }
        if (count($relations)) {
            foreach ($relations as $key => $relation) {
                $allAttributes['relations'][$key] = $relation;
            }
        }

        $log = $log_class->createLog($allAttributes);
        $item->saveLog($item->creator_id, $item, $log, 'created');

        return ApiResponse::onSuccess(200, $this->messages['create'], $item);
    }

    public function edit($id) {
        $item = $this->model::with('creator')->with('editor')
        ->with('media', function($q) {
            $q->orderBy('order_column', 'asc');
        })
        ->with('site')
        ->with('categories')
        ->with('tags')
        ->with('sources')
        ->with('direction_types')
        ->with('documents')
        ->with('workers')
        ->find($id);

        if($item) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(ArticleRequest $request, $id) {

        // dd($request->gallery);

        $item = $this->model::find($id);
        if(!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }
        // для лога
        $log_class = new LogService;
        $oldAttributes = $item->getAttributes();

        $old_title = $item->title;
        $new_title = $request->title;
        $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        if ((isset($request->slug) && $slug != $oldAttributes['slug']) or (isset($request->title) && $request->title != $oldAttributes['title'])) {
            $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=false);
        }

        $item->title = $request->title;

        $item->body = $request->body;
        $item->video = $request->video;

        $item->editor_id = $request->user()->id;
        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        if($request->is_pin) {
            $item->is_pin = 1;
            $item->pin_date = !empty($request->pin_date) ? $request->pin_date : Carbon::now();
        } else {
            $item->is_pin = 0;
        }

        $item->is_portal = !empty($request->is_portal) ? 1 : 0;

        // для лога
        $changedAttributes = $log_class->getChangedAttributes($item);
        $oldAttributes = array_intersect_key($oldAttributes, $changedAttributes);
        $old_relations = $log_class->getRelationsArray($item);

        // $item->save();


        // Приклепление медиа
        $oldPoster = $item->getMedia('article_posters')->first();
        if ($request->poster && count($request->poster)) {
            if(!isset($request->poster['id'])) {
                $item->clearMediaCollection('article_posters');
            }
            HRequest::save_poster($item, $request->poster, 'article_posters');
        } else {
            $item->clearMediaCollection('article_posters');
        }
        $item->load('media');
        $newPoster = $item->getMedia('article_posters')->first();
        $poster = $log_class->posterForLog($oldPoster, $newPoster);

        $oldGallery = $item->getMedia('article_gallery');
        if ($request->gallery && count($request->gallery)) {
            HRequest::save_gallery($item, $request->gallery, "article_gallery");
        } else {
            $item->clearMediaCollection('article_gallery');
        }
        $item->load('media');
        $newGallery = $item->getMedia('article_gallery');
        $gallery = $log_class->galleryForLog($oldGallery, $newGallery);

        // Обновление категорий
        if($request->categories && is_array($request->categories)) {
            $item->categories()->sync($request->categories);
        } else {
            $item->categories()->detach();
        }

        // Обновление тэгов
        if (is_array($request->tags) && count($request->tags)) {
            $tags_array = HRequest::tags($request->tags);
            $item->tags()->sync($tags_array);
        } else {
            $item->tags()->detach();
        }

        // Обновление источников
        if($request->sources && is_array($request->sources)) {
            $item->sources()->sync($request->sources);
        } else {
            $item->sources()->detach();
        }

        // Обновление направлений деятельности
        if($request->direction_types && is_array($request->direction_types)) {
            $item->direction_types()->sync($request->direction_types);
        } else {
            $item->direction_types()->detach();
        }

        // Обновление документов
        if($request->documents && is_array($request->documents)) {
            $item->documents()->sync($request->documents);
        } else {
            $item->documents()->detach();
        }

        // Обновление рабов
        if($request->workers && is_array($request->workers)) {
            $item->sync_with_sort($item, 'workers', $request->workers, 'worker_sort');
        } else {
            $item->workers()->detach();
        }

        $item->fresh();
        $new_relations = $log_class->getRelationsArray($item);
        $diff_relations = $log_class->compareArrays($old_relations, $new_relations);
        
        if (isset($diff_relations['old'])) {
            $oldAttributes['relations'] = $diff_relations['old'];
        }
        if (isset($diff_relations['new'])) {
            $changedAttributes['relations'] = $diff_relations['new'];
        }

        if (isset($poster['old'])) {
            $oldAttributes['relations']['media']['poster'] = $poster['old'];
        }
        if (isset($poster['new'])) {
            $changedAttributes['relations']['media']['poster'] = $poster['new'];
        }

        if (isset($gallery['old'])) {
            $oldAttributes['relations']['media']['gallery'] = $gallery['old'];
        }
        if (isset($gallery['new'])) {
            $changedAttributes['relations']['media']['gallery'] = $gallery['new'];
        }

        $log = $log_class->createLog($changedAttributes, $oldAttributes);
        $item->saveLog($item->editor_id, $item, $log, 'updated');
        
        return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);

    }

}