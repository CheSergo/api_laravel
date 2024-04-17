<?php

namespace App\Modules\Documents;

use App\Http\Controllers\Controller;

// Requests
use App\Modules\Documents\DocumentRequest;

// Filters
use App\Modules\Documents\DocumentsFilter;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

// Models
use App\Modules\Documents\Document;

class DocumentAdminController extends Controller
{
    use ActionMethods, ActionsSaveEditItem;

    /**
     * @var DocumentsFilter
     */
    public $filter;
    public $model = Document::class;
    public $messages = [
        'create' => 'Документ успешно добавлен',
        'edit' => 'Редактирование документа',
        'update' => 'Документ успешно изменен',
        'delete' => 'Документ успешно удален',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * DocumentsController constructor.
     * @param DocumentsFilter $filter
     */
    public function __construct(DocumentsFilter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $items = (object) $this->model::filter($this->filter)->orderBy('created_at', 'desc')
            ->thisSite()
            ->with('site')->with('media')->with('type')->with('interval')->with('tags')
            ->paginate(10)
            ->toArray();

        if (isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        return [
            'meta' => $meta,
            'data' => $items->data,
        ];
    }

    /**
     * @return mixed
     */
    public function list()
    {
        $items = (object) $this->model::filter($this->filter)
            ->thisSite()
            ->select(['id', 'title', 'numb', 'date', 'type_id'])
            ->orderBy('created_at', 'desc')
            ->without('editor')->without('creator')
            ->with('site')
            ->with('media')
            ->with('type')
            ->with('tags')
            ->with('sources')
            ->get();

        if (isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        return [
            'meta' => $meta,
            'data' => $items,
        ];
    }

    public function store(DocumentRequest $request)
    {
        $item = new $this->model;

        $item->title = $request->title;
        $item->numb = $request->numb;
        $item->date = !empty($request->date) ? Carbon::parse($request->date)->format('Y-m-d H:i:s') : null;

        $item->is_antimonopoly = !empty($request->is_antimonopoly) ? 1 : 0;
        if (!empty($request->number_day_expertise_antimonopoly)) {
            $item->number_day_expertise_antimonopoly = $request->number_day_expertise_antimonopoly;
        }
        $item->is_anticorruption = !empty($request->is_anticorruption) ? 1 : 0;
        if (!empty($request->number_day_expertise_anticorruption)) {
            $item->number_day_expertise_anticorruption = $request->number_day_expertise_anticorruption;
        }

        if (!empty($request->is_antimonopoly)) {
            !empty($request->date) ? $date = new Carbon($request->date) : $date = new Carbon($request->published_at);
            $item->date_antimonopoly_expertise = $date->addDays($request->number_day_expertise_antimonopoly);
        }

        if (!empty($request->is_anticorruption)) {
            !empty($request->date) ? $date = new Carbon($request->date) : $date = new Carbon($request->published_at);
            $item->date_anticorruption_expertise = $date->addDays($request->number_day_expertise_anticorruption);
        }

        $item->is_mpa = !empty($request->is_mpa) ? 1 : 0;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->creator_id = $request->user()->id;
        $item->editor_id = $request->user()->id;

        $item->site_id = $request->user()->active_site_id;
        $item->type_id = $request->type_id;
        $item->status_id = $request->status_id;

        $item->save();

        // Создание слага из рандомных байт
        $bytes = random_bytes(4);
        $item->update([
            'slug' => $item->id . 'i' . bin2hex($bytes),
        ]);

        // Обновление направлений деятельности
        // if (isset($request->directions)) {
        //     $item->directions()->sync($request->directions);
        // }

        // Прикрепление документа
        // HRequest::save_media($request->document_files, $item, 'document_files');

        // Источники
        if (isset($request->sources)) {
            $item->sources()->attach($request->sources);
        }

        if ($request->file && count($request->file)) {
            if(!$request->file['id']) {
                $item->clearMediaCollection('document_files');
            }
            HRequest::save_poster($item, $request->file, 'document_files');
        } else {
            $item->clearMediaCollection('document_files');
        }

        // Прикрепление "галлереи"
        if ($request->attachments && count($request->attachments)) {
            HRequest::save_gallery($item, $request->attachments, "document_attachments");
        }

        // Прикрепление тэгов
        if(isset($request->tags) && count($request->tags)) {
            $tags_array = HRequest::tags($request->tags);
            $item->tags()->attach($tags_array);
        }

        // Возврат данных в ответе
        $freshItem = $item->fresh();
        $freshItem->getMedia();
        $data = $freshItem;

        return ApiResponse::onSuccess(200, $this->messages['create'], $data);
    }

    public function edit($id)
    {
        if ($item = $this->model::with('site')->with('media', function ($q) {
            $q->orderBy('order_column', 'asc');
        })->with('type')->with('interval')->with('tags')->with('directions')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(DocumentRequest $request, $id)
    {
        $item = $this->model::find($id);

        if (!$item) {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

        $item->title = $request->title;
        $item->numb = $request->numb;
        $item->date = !empty($request->date) ? Carbon::parse($request->date)->format('Y-m-d H:i:s') : null;

        $item->is_antimonopoly = !empty($request->is_antimonopoly) ? 1 : 0;
        if (!empty($request->number_day_expertise_antimonopoly)) {
            $item->number_day_expertise_antimonopoly = $request->number_day_expertise_antimonopoly;
        }
        $item->is_anticorruption = !empty($request->is_anticorruption) ? 1 : 0;
        if (!empty($request->number_day_expertise_anticorruption)) {
            $item->number_day_expertise_anticorruption = $request->number_day_expertise_anticorruption;
        }

        if (!empty($request->is_antimonopoly)) {
            !empty($request->date) ? $date = new Carbon($request->date) : $date = new Carbon($request->published_at);
            $item->date_antimonopoly_expertise = $date->addDays($request->number_day_expertise_antimonopoly);
        }

        if (!empty($request->is_anticorruption)) {
            !empty($request->date) ? $date = new Carbon($request->date) : $date = new Carbon($request->published_at);
            $item->date_anticorruption_expertise = $date->addDays($request->number_day_expertise_anticorruption);
        }

        $item->is_mpa = !empty($request->is_mpa) ? 1 : 0;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();

        $item->editor_id = $request->user()->id;
        $item->site_id = $request->user()->active_site_id;
        $item->type_id = $request->type_id;
        $item->status_id = $request->status_id;

        $item->save();

        // Апдейт слага из рандомных байт, если пуст
        if (is_null($item->slug)) {
            $bytes = random_bytes(4);
            $item->update([
                'slug' => $item->id . 'i' . bin2hex($bytes),
            ]);
        }

        // // Обновление новости к документу
        // if (isset($request->article_id)) {
        //     $item->articles()->sync($request->article_id);
        // }

        // // Обновление раздела к документу
        // if (isset($request->section_id)) {
        //     $item->sections()->sync($request->section_id);
        // }

        if (isset($request->sources)) {
            $item->sources()->sync($request->sources);
        }

        // Обновление направлений деятельности
        // if (isset($request->directions)) {
            // $item->directions()->sync($request->directions);
        // }

        // Обновление документа
        if ($request->file && count($request->file)) {
            if(!isset($request->file['id'])) {
                $item->clearMediaCollection('document_files');
            }
            HRequest::save_poster($item, $request->file, 'document_files');
        } else {
            $item->clearMediaCollection('document_files');
        }

        // Обновление "галлереи"
        if ($request->attachments && count($request->attachments)) {
            HRequest::save_gallery($item, $request->attachments, "document_attachments");
        } else {
            $item->clearMediaCollection('document_attachments');
        }

        // Обновление тэгов
        $tags_array = HRequest::tags($request->tags);
        $item->tags()->sync($tags_array);

        // Возврат данных в ответе
        $item->refresh();
        $item->getMedia();
        $data = $item;

        return ApiResponse::onSuccess(200, $this->messages['update'], $data);
    }
}
