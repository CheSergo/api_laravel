<?php

namespace App\Modules\Utils;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LinkFetchController extends Controller
{

    public static function get(Request $request) {
        $html = self::fetch($request->url);

        $title = '';
        $description = '';
        $image_url = '';
        $link = $request->url;

        if(!$html) {
            return [
                'success' => 0,
                'link' => $link
            ];
        }

        //parsing begins here:
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);

        //get and display what you need:
        $title = $doc->getElementsByTagName('title')?->item(0)?->nodeValue;
        $img = $doc->getElementsByTagName('img')?->item(0);
        $metas = $doc->getElementsByTagName('meta');

        $image_url = $img?->attributes?->getNamedItem("src")?->value;

        for ($i = 0; $i < $metas->length; $i++) {
            $meta = $metas->item($i);
            if($meta->getAttribute('name') == 'description')
                $description = $meta->getAttribute('content');
        }

        return [
            'success' => 1,
            'link' => $link,
            'meta' => [
                'title' => $title ?? '',
                'site_name' => '',
                'description' => $description ?? '',
                'image' => [
                    'url' => $image_url
                ]
            ]
        ];
    }

    public static function fetch(string $url) { 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
