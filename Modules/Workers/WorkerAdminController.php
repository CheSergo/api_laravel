<?php
namespace App\Modules\Workers;

use App\Http\Controllers\Controller;

// Requests
// use Illuminate\Http\Request;
use App\Modules\Workers\WorkerRequest;
use App\Modules\Workers\WorkerFilter;

// Helpers
use Carbon\Carbon;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Workers\Worker;
use App\Modules\Sites\SocialNetworks\SocialNetwork;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class WorkerAdminController extends Controller {

    use ActionMethods, ActionsSaveEditItem;
    
    public $model = Worker::class;
    public $messages = [
        'create' => 'Персона успешно добавлена',
        'edit' => 'Редактирование персоны',
        'update' => 'Персона успешно изменена',
        'delete' => 'Персона успешно удалена',
        'not_found' => 'Элемент не найден',
    ];

    /**
     * WorkersController constructor.
     * @param WorkerFilter $filter
     */
    public function __construct(WorkerFilter $filter) {
        $this->filter = $filter;
    }

  /**
     * @return mixed
     */
    public function index()
    {
        $items = (object) $this->model::filter($this->filter)->thisSite()->orderBy('created_at', 'DESC')
        ->with('creator')
        ->with('site')
        ->with('departments')
        ->with('social_networks')
        ->paginate(10)
        ->toArray();

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

    /**
     * @return mixed
     */
    public function list() {
        $items = (object) $this->model::thisSite()/*->published()*/->filter($this->filter)/*->with('media')*/->get();
        if(isset($items->path)) {
            $meta = Meta::getMeta($items);
        } else {
            $meta = [];
        }

        return [
            'meta' => $meta,
            'data' => $items,
        ];
    }

    public function store(WorkerRequest $request) {
        $item = new $this->model;

        $item->creator_id = request()->user()->id;
        $item->editor_id = request()->user()->id;

        $item->surname = $request->surname;
        $item->name = $request->name;
        $item->second_name = $request->second_name;

        $slug = $request->surname.'-'.$request->name.'-'.$request->second_name;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=true);

        $item->position = $request->position;
        $item->email = $request->email;
        $item->phone = $request->phone;
        $item->biography = $request->biography;
        $item->credentials = $request->credentials;

        $item->site_id = request()->user()->active_site_id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;
        $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();
        
        $item->save();

        if(isset($request->social_networks) && count($request->social_networks)) {
            foreach ($request->social_networks as $s_link) {
                $social_network = new SocialNetwork;
                $social_network->code = $s_link['code'];
                $social_network->link = $s_link['link'];

                $social_network->creator_id = request()->user()->id;
                $social_network->editor_id = request()->user()->id;

                $social_network->save();
    
                $item->social_networks()->save($social_network);
            }
        }

        // Приклепление медиа
        if ($request->photo && count($request->photo)) {
            HRequest::save_poster($item, $request->photo, 'worker_photos');
        } 

        // Прикрепленеие документов
        if($request->documents && is_array($request->documents) && count($request->documents)) {
            foreach($request->documents as $index => $document) {
                $item->documents()->attach($document, ['document_sort' => $index+1]);
            }
        }

        // Возврат данных в ответе
        $freshItem = $item->fresh();
        $freshItem->getMedia();
        $data = $freshItem;

        return ApiResponse::onSuccess(200, $this->messages['create'], $data/*$data = $item*/);
    }

    public function edit($id) {
        if($item = $this->model::with('media')->with('documents')->with('social_networks')->find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(WorkerRequest $request, $id) {
        if ($item = $this->model::find($id)) {
            $item->editor_id = request()->user()->id;

            // Сначала проверяем слаг, а потом записываем новые поля: имя, фамилия, отчество
            $slug = $request->surname.'-'.$request->name.'-'.$request->second_name;
            $old_title = $item->surname.' '.$item->name.' '.$item->second_name;
            $new_title = $request->surname.' '.$request->name.' '.$request->second_name;
            $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title, $new_title, $global=true);

            $item->surname = $request->surname;
            $item->name = $request->name;
            $item->second_name = $request->second_name;

            $item->position = $request->position;
            $item->email = $request->email;
            $item->phone = $request->phone;
            $item->biography = $request->biography;
            $item->credentials = $request->credentials;

            $item->site_id = request()->user()->active_site_id;

            $item->is_published = !empty($request->is_published) ? 1 : 0;
            $item->published_at = !empty($request->published_at) ? $request->published_at : Carbon::now();
            
            $item->save();

            // Обновление соц. сетей
            $social_networks_ids = array();
            if (isset($request->social_networks) && count($request->social_networks)) {
                foreach ($request->social_networks as $s_link) {
                    if(isset($s_link['id']) && !is_null($s_link['id'])) {
                        $social_network = $item->social_networks->where('id', $s_link['id'])->first();
                        if($social_network) {
                            array_push($social_networks_ids, $s_link['id']);
                            $social_network->code = $s_link['code'];
                            $social_network->link = $s_link['link'];
                            $social_network->editor_id = request()->user()->id;
                            $social_network->creator_id = request()->user()->id;
                            $social_network->save();
                        }
                    } else {
                        $social_network = new SocialNetwork;
                        $social_network->code = $s_link['code'];
                        $value = $s_link['link'];
                        if (!preg_match("/^http(s)?:\/\//", $value)) {
                            $value = "http://" . $value;
                        }
                        $social_network->link = $s_link['link'];
                        $social_network->editor_id = request()->user()->id;
                        $social_network->creator_id = request()->user()->id;
                        $social_network->save();

                        array_push($social_networks_ids, $social_network->id);

                        $item->social_networks()->attach($social_network);
                    }
                }
            }
            $social_networks_to_del = array_diff($item->social_networks->pluck('id')->toArray(), $social_networks_ids);
            foreach($social_networks_to_del as $link_to_del) {
                $link_to_del = $item->social_networks->where('id', $link_to_del)->first();
                $item->social_networks()->detach($link_to_del->id);
                $link_to_del->delete();
            }
    

            // Приклепление медиа
            if ($request->photo && count($request->photo)) {
                HRequest::save_poster($item, $request->photo, 'worker_photos');
            } else {
                $item->clearMediaCollection('worker_photos');
            }

            // Обновление документов
            $item->sync_with_sort($item, 'documents', $request->documents, 'document_sort');

            return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);

        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

}