<?php
namespace App\Helpers;

class Meta {

    static function getMeta($items) {
        $meta = [
            'path'          => $items->path,
            'route'         => \Request::route()->getName(),
            'pagination' => [
                'to'            => $items->to,
                'from'          => $items->from,
                'total'         => $items->total,
                'current_page'  => $items->current_page,
                'per_page'      => $items->per_page,
                'last_page'     => $items->last_page,
            ],
        ];

        return $meta;
    }

    static function processItems($items, $key, $fields) {
        $processedItems = [];
        foreach ($items as $item) {
            if (!empty($item)) {
                foreach ($item as $singleItem) {
                    $processedItems[$singleItem[$key]] = array_intersect_key($singleItem, array_flip($fields));
                }
            }
        }
        return array_values($processedItems);
    }
    
    static function sorting_by_title(&$items) {
        usort($items, function($a, $b) {
            return strcmp($a['title'], $b['title']);
        });
    }
}

?>