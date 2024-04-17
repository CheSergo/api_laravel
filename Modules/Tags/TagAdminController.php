<?php

namespace App\Modules\Tags;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;

// Helpers
use Str;
use App\Helpers\Meta;
use App\Helpers\HRequest;
use App\Helpers\Api\ApiResponse;

// Models
use App\Modules\Tags\Tag;

// Filters
use App\Modules\Tags\TagsFilter;

// Traits
use App\Traits\Actions\ActionMethods;

class TagAdminController extends Controller 
{
    use ActionMethods;

    /**
     * @var TagsFilter
     */
    public $filter;
    public $model = Tag::class;
    public $messages = [
        'create' => 'Тэг успешно добавлен',
        'edit' => 'Редактирование тэга',
        'update' => 'Тэг успешно изменен',
        'delete' => 'Тэг успешно удален',
        'exist' => 'Тэг с таким именем уже существует',
        'not_found' => 'Элемент не найден',
    ];

    public function __construct(TagsFilter $filter) {
        $this->filter = $filter;
    }

    public function index()
    {
        $items = (object) $this->model::filter($this->filter)->orderBy('created_at', 'DESC')->withCount('articles')->paginate(54)->toArray();

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

    public function list() {
        $items = (object) $this->model::orderBy('created_at', 'DESC')->select('id', 'title')->get();

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
            'title' => 'required|string|max:191',
            // 'code' => 'string|max:191',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        if($tag_item = Tag::where('title', $request->title)->first()) {
            if(Str::lower($request->title) != Str::lower($tag_item->title)) {
                $new_tag = new Tag;
                $new_tag->title = $request->title;
                // $new_tag->code = strtolower(HString::transliterate($request->title));
                $new_tag->code = HRequest::slug($new_tag, $new_tag->id, $request->title, $type='code', $old_title=null, $new_title=null, $global=true);
                $new_tag->save();

                return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
                    'id' => $new_tag->id,
                    'title' => $new_tag->title,
                ]);
            } else {
                return ApiResponse::onSuccess(200, $this->messages['exist'], $data = [
                    'id' => $tag_item->id,
                    'title' => $tag_item->title,
                ]);
            }
        } else {
            $new_tag = new Tag;
            $new_tag->title = $request->title;
            // $new_tag->code = strtolower(HString::transliterate($request->title));
            $new_tag->code = HRequest::slug($new_tag, $new_tag->id, $request->title, $type='code', $old_title=null, $new_title=null, $global=true);
            $new_tag->save();
            return ApiResponse::onSuccess(200, $this->messages['create'], $data = [
                'id' => $new_tag->id,
                'title' => $new_tag->title,
            ]);
        };
    }
    
    public function edit($id) {
        if($item = $this->model::find($id)) {
            return ApiResponse::onSuccess(200, $this->messages['edit'], $data = $item);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            // 'code' => 'string|max:191',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
        }

        if($item = $this->model::find($id)) {

            $tag_item = Tag::where('title', $request->title)->whereNot('id', $item->id)->first();
            if($tag_item) {
                if(Str::lower($request->title) != Str::lower($tag_item->title)) {
                    $item->title = $request->title;
                    // $item->code = strtolower(HString::transliterate($request->title));
                    $item->code = HRequest::slug($item, $item->id, $request->code, $type='code', $old_title=null, $new_title=null, $global=true);
                    $item->save();
                    return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
                        'id' => $item->id,
                        'title' => $item->title,
                    ]);
                } else {
                    return ApiResponse::onSuccess(200, $this->messages['exist'], $data = [
                        'id' => $tag_item->id,
                        'title' => $tag_item->title,
                    ]);
                }
            }
            $item->title = $request->title;
            $item->code = HRequest::slug($item, $item->id, $request->code, $type='code', $old_title=null, $new_title=null, $global=true);
            // $item->code = strtolower(HString::transliterate($request->code));
            $item->save();
            return ApiResponse::onSuccess(200, $this->messages['update'], $data = [
                'id' => $item->id,
                'title' => $item->title,
            ]);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        }
    }
}