<?php
namespace App\Traits\Common;

trait GetSearchParams {

    public function getSearchParamsAttribute()
    {
        $url = "#";
        $title = "";
        $text = "";

        if ($this->table == 'articles') {
            $url = "/news/".$this->slug;
        } else if ($this->table == 'documents') {
            $url = "/docs/document-".$this->slug;
        } else if ($this->table == 'operational_informations') {
            $url = "/operational-informations/".$this->id;
        } else if ($this->table == 'directions') {
            $code = \App\Modules\Directions\DirectionTypes\DirectionType::published()
                ->where('id', $this->type_id)->pluck('code')->first();
            $url = "/directions/".$code."/".$this->slug;
        } else if ($this->table == 'workers') {
            $type_id = $this->departments()->pluck('id')->first();
            if ($type_id && !is_null($type_id)) {
                $department = \App\Modules\Departments\Department::published()
                    ->where('id', $type_id)->first();
                $code = isset($department->type_id) ? \App\Modules\Departments\DepartmentTypes\DepartmentType::published()
                    ->where('id', $department->type_id)->pluck('code')->first() : '';
                $path = \App\Modules\Sections\Section::published()
                    ->where('reroute', 'like', '%type='.$code.'%')->pluck('path')->first();
                $url = $path."/person-".$code."-".$this->slug;
            }
        } else if ($this->table == 'government_informations') {
            $url = $this->path."/government-information-".$this->slug;
        } else if ($this->table == 'municipal_services') {
            $url = '/directions/municipalnye-uslugi/reestr-municipalnyh-uslug#'.$this->id;
        } else {
            $url = isset($this->path) ? $this->path : $url;
        }

        if (isset($this->title) && !empty($this->title)) {
            $title = $this->title;
        } else if ($this->table == 'workers') {
            $secondName = $this->second_name ? ' '. $this->second_name : '';
            $title = $this->surname.' '.$this->name.$secondName;
        }

        if (isset($this->body) && !empty($this->body) && is_array($this->body)) {
            $items = [];
            foreach ($this->body['blocks'] as $value) {
                if ($value['type'] === 'header') {
                    $items[] = $value['data']['text'];
                }
                if ($value['type'] === 'paragraph') {
                    $items[] = $value['data']['text'];
                }
                if ($value['type'] === 'list') {
                    foreach ($value['data']['items'] as $item) {
                        $items[] = $item;
                    }
                }
            }
            $text = implode("", $items);
        }

        return [
            'table_name' => $this->table,
            'search_fields' => [
                'title' => $title,
                'text' => $text,
                'url' => $url,
            ]
        ];
    }
}