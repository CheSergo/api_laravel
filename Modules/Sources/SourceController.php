<?php

namespace App\Modules\Sources;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// Filters
use App\Modules\Sources\SourcesFilter;

// Requests
use Illuminate\Http\Request;

// Helpers
use Carbon\Carbon, Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\HString;
use App\Helpers\Api\ApiResponse;

//Models
use App\Modules\Sources\Source;

// Traits
use App\Traits\Actions\ActionMethods;
use App\Traits\Actions\ActionsSaveEditItem;

class SourceController extends Controller 
{
    use ActionsSaveEditItem, ActionMethods;

    public $model = Source::class;
    public $messages = [
        'create' => 'Источник успешно добавлен',
        'edit' => 'Редактирование источника',
        'update' => 'Источник успешно изменен',
        'delete' => 'Источник успешно удален',
        'not_found' => 'Элемент не найден',
        'exist' => 'Источник с таким именем уже существует',
    ];

    public $customMessages = [
        'title.required' => 'Введите название',
        'title.unique' => 'Источник с таким названием уже существует',
        'title.max' => 'Максимально допустимая длина названия 255 символов',
        'description.max' => 'Максимальная длина описания 255 символов',
        'path.max' => 'Максимальная длина пути 191 символ',
        'slug.max' => 'Максимальная длина слага 255 символов',
        'slug.unique' => 'Источник с таким названием уже существует',
    ];

    /**
     * @return mixed
     */
    public function index(SourcesFilter $filter)
    {
        $items = (object) $this->model::filter($filter)->orderBy('title', 'ASC')
            ->paginate(18)->toArray();

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

        $items = $this->model::published()->select('id', 'title')->orderBy('title', 'ASC')->get();

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

    public function store(Request $request) {
        
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sources'),
            ],
            'description' => 'string|max:255|nullable',
            'path' => 'string|max:191|nullable',
            'slug' => [
                'string',
                'max:255',
                'nullable',
                Rule::unique('sources'),
            ],
        ]);

        $validator->setCustomMessages($this->customMessages);
        
        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        if($source = $this->model::where('title', $request->title)->first()){
            return ApiResponse::onSuccess(200, $this->messages['exist'], $data = [
                'id' => $source->id,
                'title' => $source->title,
            ]);
        }
        $item = new $this->model;

        $item->title = $request->title;

        if(isset($request->slug) && !is_null($request->slug)) {
            $slug = $request->slug;
        } else {
            // Создание слага из первых букв каждого слова из title, исключая предлоги (т.е. слова короче 3х букв)
            $string = preg_replace('/[^\p{L}\s]/u', '', $item->title);
            $arr = explode(" ", $string);
            $string = '';
            foreach ($arr as $str) {
                if(mb_strlen($str) > 3) {
                    $string = $string.mb_substr($str, 0, 3);
                }
            }
            $slug = HString::transliterate(mb_strtolower($string));
        }
        // $item->slug = HString::transliterate(mb_strtolower($string));

        // $slug = isset($request->slug) && !is_null($request->slug) ? $request->slug : $request->title;
        $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=true);

        $item->description = $request->description;
        $item->link = $request->link;

        $item->disctrict_id = $request->disctrict_id;
        $item->creator_id = $request->user()->id;
        $item->editor_id = $request->user()->id;

        $item->is_published = !empty($request->is_published) ? 1 : 0;

        $item->save();

        return ApiResponse::onSuccess(200, $this->messages['create'], $data = $item);
    }

    public function edit($id) {
        if($item = $this->model::find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(Request $request, $id) {

        if($item = $this->model::find($id)) {

            $validator = Validator::make($request->all(), [
                'title' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('sources')->ignore($item->id),
                ],
                'description' => 'string|max:255|nullable',
                'path' => 'string|max:191|nullable',
                'slug' => [
                    'string',
                    'max:255',
                    'nullable',
                    Rule::unique('sources')->ignore($item->id),
                ],
            ]);

            $validator->setCustomMessages($this->customMessages);

            if ($validator->fails()) {
                return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
            }

            if($source = $this->model::where('title', $request->title)->whereNot('id', $id)->first()){
                return ApiResponse::onSuccess(200, $this->messages['exist'], $data = [
                    'id' => $source->id,
                    'title' => $source->title,
                ]);
            }
            
            // Создание слага из первых букв каждого слова из title, исключая предлоги (т.е. слова короче 3х букв)
            $item->title = $request->title;
            if(isset($request->slug) && !is_null($request->slug)) {
                $slug = $request->slug;
            } else {
                $string = preg_replace('/[^\p{L}\s]/u', '', $item->title);
                $arr = explode(" ", $string);
                $string = '';
                foreach ($arr as $str) {
                    if(mb_strlen($str) > 3) {
                        $string = $string.mb_substr($str, 0, 3);
                    }
                }
                $slug = HString::transliterate(mb_strtolower($string));
            }
            $item->slug = HRequest::slug($this->model, $item->id, $slug, $type='slug', $old_title=null, $new_title=null, $global=true);

            $item->description = $request->description;
            $item->link = $request->link;

            $item->disctrict_id = $request->disctrict_id;
            $item->editor_id = $request->user()->id;
    
            $item->is_published = !empty($request->is_published) ? 1 : 0;
    
            $item->save();
    
            return ApiResponse::onSuccess(200, $this->messages['update'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }

    }
}