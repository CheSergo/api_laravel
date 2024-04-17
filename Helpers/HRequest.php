<?php
namespace App\Helpers;

use Illuminate\Support\Str;

use App\Modules\Tags\Tag;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

use App\Helpers\HString;
use App\Traits\Actions\ActionsSaveEditItem;

class HRequest { 

    static function createHash($filename) {
        $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
        $hash = hash('sha256', $filename);
        return substr($hash, 0, 140).'.'.$fileExtension;
    }

    static function find_item($model, $id, $type, $slug, $global){
        if ($global) {
            return $model::where($type, $slug)->whereNot('id', $id)->first();
        } else {
            return $model::where($type, $slug)->where('site_id', request()->user()->active_site_id)->whereNot('id', $id)->first();
        }
    }

    static function slug($model, $id, $slug, string $type='slug', $old_title=null, $new_title=null, $global=false) {

        if ( (!is_null($old_title) && !is_null($new_title) && mb_strtolower($new_title) != mb_strtolower($old_title) ) ) {
            $slug = Str::of(mb_substr(strtolower(HString::transliterate($new_title)), 0, 240))->slug('-');
        } else {
            $slug = Str::of(mb_substr(strtolower(HString::transliterate($slug)), 0, 240))->slug('-');
        }
    
        while (true) {
            $item = HRequest::find_item($model, $id, $type, $slug, $global);
            if(!$item) {
                return $slug;
            } else {
                $bytes = random_bytes(2);
                $slug = $slug."-".bin2hex($bytes);
            }
        }
    }

    // Документы
    // Возможно он нигде уже не используется
    static function update_docs($docs, $item) {
        if(isset($docs)) {
            $item->documents()->detach();
            if(count($docs)) {
                foreach ($docs as $key => $document) {
                    if(isset($document)) {
                        $item->documents()->attach($document, ['document_sort' => $key]);
                    }
                }
            } 
        }
    }

    // Работа с медиа
    static function removeAllButCharsAndDigits(string $fileName): string
    {
        $nstr = preg_replace("/\s+/", '_', $fileName);
        $nstr = preg_replace("/[^a-zA-Z.0-9,а-яА-Я_№(\)\/-]/iu", '', $nstr);
        // $nstr = preg_replace('/[^a-zA-Z0-9_.]/', '', $fileName);
        $nstr = preg_replace("/\-+/", '-', $nstr);
        $nstr = preg_replace("/\_+/", '_', $nstr);
        $nstr = preg_replace("/[()]+/", '_', $nstr);
        return $nstr;
        // return pathinfo($nstr, PATHINFO_FILENAME);
    }
    // Одиночная медия (Постер)
    static function save_poster(object $model, array $file, string $collection = 'public') {
        if($file && count($file)) {
            $id = $file["id"] ?? isset($file["id"]) && !is_null($file["id"]);
            $filename = isset($file["filename"]) ? HRequest::createHash($file["filename"]) : '';
            $name = isset($file["name"]) ? mb_substr($file["name"], 0, 255) : preg_replace('/\.\w+$/', '', $filename);
            // $name = HRequest::removeAllButCharsAndDigits($name);
            $base64 = isset($file["base64"]) ? $file["base64"] : '';
            
            if ($id) {
                $media = Media::find($file['id']);
                $media->name = $name;
                $media->save();
            } else {
                if($base64) {
                    if($name && $filename) { 
                        $model->addMediaFromBase64($base64)
                        ->usingName($name)
                        ->usingFileName($filename)
                        ->toMediaCollection($collection);
                    } else {
                        $model->addMediaFromBase64($base64)->toMediaCollection($collection);
                    }
                }
            }

            
        } 
    }

    // Массив медиа
    static function save_gallery(object $model, array $files, string $collection = 'public') {
        if($files && count($files)) {
            $fileIds = [];

            foreach($files as $index => $file) {
                $filename = isset($file['filename']) ? $file['filename'] : '';
                $name = isset($file['name']) ? $file['name'] : preg_replace('/\.\w+$/', '', $filename);
                // $name = HRequest::removeAllButCharsAndDigits($name);
                $base64 = isset($file['base64']) ? $file['base64'] : '';
                
                if($base64) {
                    if($name && $filename) { 
                        $media = $model->addMediaFromBase64($base64)
                            ->usingName($name)
                            ->usingFileName($filename)
                            ->toMediaCollection($collection);
                    } else {
                        $media = $model->addMediaFromBase64($base64)->toMediaCollection($collection);
                    }
                    
                    $media->order_column = $index + 1;
                    $media->save();
                    
                    $fileIds[] = $media->id;
                } else {
                    if (!empty($file['id'])) {
                        $media = Media::find($file['id']);
                        $media->name = $name;
                        $media->order_column = $index + 1;
                        $media->save();
                        
                        $fileIds[] = $media->id;
                    }
                }
            }
            // Удаляем старые медиа
            $model->getMedia($collection)
            ->whereNotIn('id', $fileIds)
            ->each(function ($media) {
                $media->delete();
            });
        } else {
            $model->clearMediaCollection($collection);
        }
    }

    static function tags(array $tags) {
        $tagsArray = [];

        if(count($tags)) {
            foreach ($tags as $key => $tag) {
                if(is_numeric($tag)) {
                    $tagsArray[$key] = $tag;
                } else {
                    $code = Str::lower(HString::transliterate($tag));
                    $tag_item = Tag::where('code', $code)->first();
                    if(!$tag_item) {
                        $tag_item = new Tag;
                        $tag_item->title = $tag;
                        $tag_item->code = $code;
                        $tag_item->save();
                    }
                    $tagsArray[$key] = $tag_item->id;
                }
            }
        }

        return $tagsArray;
    }

    // Скорее всего уже нигде не используется
    static function update_tags($tags, $item) {
        if(!empty($tags) && is_array($tags)) {
            $item->tags()->detach();
            if(count($tags)) {
                foreach ($tags as $tag) {
                    if(!is_null($tag['value']) && !is_null($tag['label'])) {
                        if($tag_item = Tag::find($tag['value'])) {
                            if(Str::lower($tag['label']) != Str::lower($tag_item->title)) {
                                $new_tag = new Tag;
                                $new_tag->title = $tag['label'];
                                $new_tag->code = HString::transliterate($tag['label']);
                                $new_tag->save();
                
                                $item->tags()->attach($new_tag);
                            } else {
                                $item->tags()->attach($tag_item);
                            }
                        } else {
                            $new_tag = new Tag;
                            $new_tag->title = $tag['label'];
                            $new_tag->code = HString::transliterate($tag['label']);
                            $new_tag->save();
            
                            $item->tags()->attach($new_tag);
                        };
                    }
                }
            }
        } else {
            $item->tags()->detach();
        }
    }

    // РАБЫ
    //Запись
    static function save_persons($persons, $item) {
        if(!empty($persons)) {
            if(count($persons)) {
                foreach ($persons as $key => $person) {
                    $item->workers()->attach($person, ['worker_sort' => $key]);
                }
            }
        }
    }
    // Обновление рабов
    static function update_persons($persons, $item) {
        if(isset($persons)) {
        $item->workers()->detach();
            if(count($persons)) {
                foreach ($persons as $key => $person) {
                    $item->workers()->attach($person, ['worker_sort' => $key]);
                }
            }
        }
    }

    static function json_save_image($body, $item, $media_collection) {
        // $body = $request->body;

        $media_files = $item->getMedia($media_collection)->pluck('id')->toArray();
        $json_media_files = [];
        foreach($body['blocks'] as $index => &$elem) {
            if(isset($elem['type']) && $elem['type'] == 'image') {
                if(isset($elem['data']['file']['base64']) && !is_null($elem['data']['file']['base64'])) {
                    // Добавил запись расширения файла
                    //$media_id = $item->addMediaFromBase64($elem['data']['file']['base64'])->toMediaCollection($media_collection)->id;
                    $media_id = $item->addMediaFromBase64($elem['data']['file']['base64'])
                        ->usingName(pathinfo($elem['data']['file']['filename'])['filename'])
                        ->usingFileName($elem['data']['file']["filename"])
                        ->toMediaCollection($media_collection)->id;
                    $media = Media::find($media_id);
                    $media->order_column = $index + 1;
                    // Изменил путь к хранилищу файлов
                    //$url = "https://api-msu.astrobl.ru".$media->getUrl();
                    $url = env('STORAGE_URL').$media->getUrl();
                    $elem['data']['file']['url'] = $url;
                    $elem['data']['file']['id'] = $media_id;
                    $elem['data']['file']['path'] = $media->getUrl();
                    unset($elem['data']['file']['base64']);
                    // unset($elem['data']['file']['active']);
                    array_push($json_media_files, $media_id);
                }
                if(isset($elem['data']['file']['id']) && !is_null(isset($elem['data']['file']['id']))) {
                    array_push($json_media_files, $elem['data']['file']['id']);
                    $media = Media::find($elem['data']['file']['id']);
                    $media->order_column = $index + 1;
                }
            }
        }
        $media_to_del = array_diff($media_files, $json_media_files);
        foreach($media_to_del as $id) {
            $d = Media::find($id);
            $d->delete();
        }
        // return $body;
        $item->body = $body;
        $item->save();
    }

    static function json_save_image_test($body, $item, $media_collection) {
        // $body = $request->body;
// dd('go go');
        $media_files = $item->getMedia($media_collection)->pluck('id')->toArray();
        $json_media_files = [];
        foreach($body['blocks'] as $index => &$elem) {
            if(isset($elem['type']) && $elem['type'] == 'image') {
                if(isset($elem['data']['file']['base64']) && !is_null($elem['data']['file']['base64'])) {
                    // Добавил запись расширения файла
                    //$media_id = $item->addMediaFromBase64($elem['data']['file']['base64'])->toMediaCollection($media_collection)->id;
                    $media_id = $item->addMediaFromBase64($elem['data']['file']['base64'])
                        ->usingName(pathinfo($elem['data']['file']['filename'])['filename'])
                        ->usingFileName($elem['data']['file']["filename"])
                        ->toMediaCollection($media_collection)->id;
                    $media = Media::find($media_id);
                    $media->order_column = $index + 1;
                    // Изменил путь к хранилищу файлов
                    //$url = "https://api-msu.astrobl.ru".$media->getUrl();
                    $url = env('API_ENV') == "development" ? env('STORAGE_URL_DEV').$media->getUrl() : env('STORAGE_URL').$media->getUrl() ;
                    $elem['data']['file']['url'] = $url;
                    $elem['data']['file']['id'] = $media_id;
                    unset($elem['data']['file']['base64']);
                    // unset($elem['data']['file']['active']);
                    array_push($json_media_files, $media_id);
                }
                if(isset($elem['data']['file']['id']) && !is_null(isset($elem['data']['file']['id']))) {
                    // array_push($json_media_files, $elem['data']['file']['id']);
                    // $media = Media::find($elem['data']['file']['id']);
                    // $media->order_column = $index + 1;

                    $old_url = str_replace('s3.astrobl.ru/msu', 's3-msu.astrobl.ru/dev', $elem['data']['file']['url']);
                    // dd($old_url);
                    
                    $media_id = $item->addMediaFromUrl($old_url)
                        ->usingName(pathinfo($elem['data']['file']['filename'])['filename'])
                        ->usingFileName($elem['data']['file']["filename"])
                        ->toMediaCollection('instruction_gallery')->id;
                    // dd($old_url);
                    
                    $media = Media::find($media_id);
                    $media->order_column = $index + 1;
                    $url = env('API_ENV') == "development" ? env('STORAGE_URL_DEV').$media->getUrl() : env('STORAGE_URL').$media->getUrl();

                    $elem['data']['file']['url'] = $url;
                    $elem['data']['file']['id'] = $media_id;

                }
            }
        }
        $media_to_del = array_diff($media_files, $json_media_files);
        foreach($media_to_del as $id) {
            $d = Media::find($id);
            $d->delete();
        }
        // return $body;
        $item->body = $body;
        $item->save();
    }
}