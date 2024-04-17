<?php
namespace App\Traits\Attributes;

use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait Clips {
    public function vkResponse($id) {
        try {
            $response = Http::timeout(3)->get('https://api.vk.com/method/video.get?videos='.$id.'&v=5.131&access_token='.env('API_VK_KEY'));
            $collection = $response->collect();
            if (isset($collection['response']['items']) && count($collection['response']['items'])) {
                return $collection['response']['items']['0'];
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    public function parseVideoAttribute() {
        $items = [];
        $videos = json_decode($this->attributes['video'], true);
        if (is_array($videos) && count($videos)) {
            foreach ($videos as $key => $clip) {
                $link = $clip['link'];
                $item = [];
                if (strpos($link, 'vk.com') !== false) {
                    $pattern = "/^.*(video(-?\d+)_(\d+)).*$/";
                    if (str_contains($link, '<iframe ')) {
                        preg_match('/src="([^"]+)"/i', $link, $matches);
                        if (count($matches)) {
                            preg_match('/"([^"]+)"/', $matches[0], $getted_link);
                        } else {
                            continue;
                        }
                        $link = $getted_link[count($getted_link)-1];
                    }
                    $parsedUrl = parse_url($link);

                    if (isset($parsedUrl['query'])) {
                        $query = $parsedUrl['query'];
                    }
                    if (isset($query)) {
                        parse_str($query, $params);
                        if (isset($params['oid']) && isset($params['id']) ) {
                            $clipId = $params['oid']."_".$params['id'];
                        } else if (isset($params['z'])) {
                            preg_match($pattern, $link, $matches);
                            if(!count($matches)) {
                                continue;
                            }
                            $clipId = $matches[2] . "_" . $matches[3];
                        }
                    } else {
                        preg_match($pattern, $link, $matches);
                        if(!count($matches)) {
                            continue;
                        }
                        $clipId = $matches[2] . "_" . $matches[3];
                    }
                    $responseVk = $this->vkResponse($clipId);
                    if (!is_null($responseVk)) {
                        $poster_url = '';
                        $width = 0;
                        if(count($responseVk['image'])) {
                            foreach ($responseVk['image'] as $arr) {
                                if ($width < $arr["width"]) {
                                    $poster_url = $arr["url"];
                                    $width = $arr["width"];
                                }
                            }
                        } else if (count($responseVk['first_frame'])) {
                            foreach ($responseVk['first_frame'] as $arr) {
                                if ($width < $arr["width"]) {
                                    $poster_url = $arr["url"];
                                    $width = $arr["width"];
                                }
                            }
                        } else {
                            return null;
                        }
    
                        $item['id'] = $key+1;
                        $item['poster'] = $poster_url;
                        $item['player'] = $responseVk['player'];
                        $item['type'] = 'vk';
                        array_push($items, $item);
                    } 
                } elseif (strpos($link, 'youtube.com') !== false) {
                    continue;
                    // if (str_contains($link, '<iframe ')) {
                    //     preg_match('/src="([^"]+)"/i', $link, $matches);
                    //     if (count($matches)) {
                    //         preg_match('/"([^"]+)"/', $matches[0], $getted_link);
                    //     } else {
                    //         continue;
                    //     }
                    //     $link = $getted_link[count($getted_link)-1];
                    // }
                    // $regex = "/^.*((youtu.be\\/)|(v\\/)|(\\/u\\/w\\/)|(embed\\/)|(watch\\?))\\??v?=?([^#&?]*).*/";
                    // preg_match($regex, $link, $matches);
                    // if(!count($matches)) {
                    //     continue;
                    // }
                    // $clipId = $matches[7];
    
                    // $item['id'] = $key+1;
                    // $item['poster'] = "https://img.youtube.com/vi/".$clipId."/maxresdefault.jpg";
                    // $item['player'] = "https://www.youtube.com/embed/".$clipId;
                    // $item['type'] = 'youtube';
                    // array_push($items, $item);
                } elseif (strpos($link, 'rutube.ru') !== false) {
                    if (str_contains($link, '<iframe ')) {
                        preg_match('/src="([^"]+)"/i', $link, $matches);
                        if (count($matches)) {
                            preg_match('/"([^"]+)"/', $matches[0], $getted_link);
                        } else {
                            continue;
                        }
                        $link = $getted_link[count($getted_link)-1];
                    }

                    $link_parts = explode("/", $link);
                    if(!count($link_parts)) {
                        continue;
                    }

                    if (substr($link, -1) === "/") {
                        $clipId = $link_parts[count($link_parts)-2];
                    } else {
                        $clipId = $link_parts[count($link_parts)-1];
                    }
    
                    $item['id'] = $key+1;
                    $item['poster'] = "https://rutube.ru/api/video/".$clipId."/thumbnail/?redirect=1";
                    $item['player'] = "https://rutube.ru/play/embed/".$clipId;
                    $item['type'] = 'rutube';
                    array_push($items, $item);
                }
            }
        }
        return $items;
    }

    public function clips(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->parseVideoAttribute(),
        );
    }
}