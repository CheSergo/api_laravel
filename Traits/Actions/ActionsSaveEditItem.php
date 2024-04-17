<?php
namespace App\Traits\Actions;

use Illuminate\Support\Str;

use App\Helpers\HString;

use App\Models\Common\Tag;
use App\Models\Common\Content;
use App\Models\Common\Document;
use App\Models\Common\Direction;
use App\Models\Sites\Archive\Exhibition;

/**
 * Trait MediaItemsSaveEdit
 * @package App\Traits\Actions
 * Созранение, изменение, удаление медиа элементов
 */
trait ActionsSaveEditItem {

    /**
     * @param $data
     * @param $item
     * @param $collection
     * Создание, изменения, удаление постера
     */
    private function detailImageAction($data, $item, $collection)
    {
        if (!empty($data['deleteId']))
            $item->deleteMedia($data['deleteId']);

        if (!empty($data['replace']))
            $item->addMediaFromBase64($data['data'])->usingName($data['name'])->usingFileName($data['filename'])->toMediaCollection($collection);
    }

    /**
     * @param $request
     * @param $item
     * @param $collection
     * Создание, изменения, удаление галереи
     */
    private function multiImagesAction($request, $item, $collection)
    {
        if (!empty($request->images))
        {
            if ($item->getMedia($collection)->count())  // Если есть прикреплённые картинки
            {
                foreach ($item->getMedia($collection) as $images)
                {
                    $item->deleteMedia($images->id);
                }
            }

            foreach ($request->images as $key => $value)
            {
                if (!is_null($value)) {
                    $image[$key] = json_decode($value);
                    $item->addMediaFromBase64($image[$key]->data)->usingName($collection.'_'.$image[$key]->id)->usingFileName($image[$key]->name)->toMediaCollection($collection);
                }
            }
        }

        if (!empty($request->delete_images_gallery[0]))
        {
            $del_file_id = explode(",", $request->delete_images_gallery[0]);
            foreach ($del_file_id as $del_id)
            {
                $item->deleteMedia($del_id);
            }
        }

    }

    /**
     * @param $tags
     * @param $item
     * @param string $title
     * Создание, изменения, удаление постера
     */
    private function tagsAction($tags, $item, $title = 'title')
    {
        $tagsId = [];
        if (!empty($tags)) {
            foreach ($tags as $key => $tag) {
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::of($tag)->slug('-')],
                    [$title => $tag]
                );
                $tagsId[$key] = $tag->id;
            }
        }
        $item->tags()->sync($tagsId);
    }

    /**
     * @param $title
     * @param $site // Если указать 0, то проверка по сайту не произойдёт
     * @param $model
     * @param string $field
     * @return \Illuminate\Support\Stringable|string
     */
    private function uniqueSlug($title, $site, $model, $field = 'slug')
    {
        // $title = HString::transliterate($title);
        strlen($title) > 240 ? $slug = Str::of(mb_substr($title, 0, 240))->slug('-') : $slug = Str::of($title)->slug('-');
        if ($site == 0) {
            $item = $model::where('slug', $slug)->first();
        } else {
            $item = $model::where('slug', $slug)->where('site_id', $site)->first();
        }
        function create_slug($slug, $model) {
            $bytes = random_bytes(2);
            $new_slug = $slug."-".bin2hex($bytes);

            $item = $model::where('slug', $new_slug)->first();
            if(!$item) {
                return $new_slug;
            } else {
                create_slug($slug, $model);
            }
        }

        if($item) {
            return create_slug($slug, $model);
        } else {
            return $slug;
        }
    }


}