<?php

namespace App\Modules\Logs;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LogService
{

    public $list_of_relations = [
        'abilities',
        'articles',
        'categories',
        'components',
        'directions',
        'documents',
        'districts',
        'roles',
        'site',
        'social_networks',
        'themes',
        'tags',
        'workers',
        'sources',
    ];


    /**
     * 
     * @return array
     */
    public function createLog(array $newAttr, array $oldAttr = null): array
    {
        if (is_null($oldAttr)) {
            return [
                'new' => $newAttr,
            ];
        } 
        return [
            'old' => $oldAttr,
            'new' => $newAttr,
        ];
    }

    /**
     * Get the attributes that have been changed since the model was last retrieved or saved.
     *
     * @return array
     */
    public function getChangedAttributes($item)
    {
        $changed = [];
        foreach ($item->getAttributes() as $key => $value) {
            if ($item->isDirty($key)) {
                $changed[$key] = $value;
            }
        }
        return $changed;
    }

    public function getRelationsArray($model): array
    {
        $relations = [];
        foreach ($this->list_of_relations as $relation) {
            $rel_Ids = $this->getRelationshipValues($model, $relation);
            if(!is_null($rel_Ids)) {
            // if(!is_null($rel_Ids) && (!is_array($rel_Ids) || count($rel_Ids))) {
                $relations[$relation] = $rel_Ids;
            }
        }
        return $relations;
    }

     /**
     * Get the values of a many-to-many relationship.
     *
     * @param mixed $model
     * @param string $relation
     * @return array
     */
    public function getRelationshipValues($model, string $relation)
    {
        if ($model->isRelation($relation)) {
            return $model->$relation()->get()->pluck('id')->toArray();
        }
    }

    public function compareArrays($old, $new): array
    {
        $changes = [];

        // Compare old array with new array
        foreach ($old as $key => $oldValue) {
            if (isset($new[$key])) {
                $newValue = $new[$key];
                if ($oldValue !== $newValue) {
                    $changes['old'][$key] = $oldValue;
                    $changes['new'][$key] = $newValue;
                }
            } else {
                $changes['old'][$key] = $oldValue;
                $changes['new'][$key] = null;
            }
        }

        // Check for new keys in the new array
        foreach ($new as $key => $newValue) {
            if (!isset($old[$key])) {
                $changes['old'][$key] = null;
                $changes['new'][$key] = $newValue;
            }
        }

        return $changes;
    }

    public function posterForLog($old, $new): array
    {

        $result = [];
        if ($old !== $new) {
            if ($new?->id != $old?->id) {
                $result['old']['id'] = $old?->id;
                $result['new']['id'] = $new?->id;
            }
            if ($new?->name != $old?->name) {
                $result['old']['name'] = $old?->name;
                $result['new']['name'] = $new?->name;
            }
        }

        return $result;
    }

    public function galleryForLog($oldGallery, $newGallery): array
    {
        $result = [];
        if ($oldGallery !== $newGallery) {
            foreach ($newGallery as $key => $pic) {
                $old_pic = $oldGallery[$key] ?? null;
                if ($pic?->id != $old_pic?->id) {
                    $result['old'][$key]['id'] = $old_pic?->id;
                    $result['new'][$key]['id'] = $pic?->id;
                }
                if ($pic?->name != $old_pic?->name) {
                    $result['old'][$key]['name'] = $old_pic?->name;
                    $result['new'][$key]['name'] = $pic?->name;
                }
            }
            if (!count($newGallery) && count($oldGallery)) {
                foreach ($oldGallery as $key => $old_pic) {
                    $result['old'][$key]['id'] = $old_pic?->id;
                    $result['new'][$key]['id'] = null;

                    $result['old'][$key]['name'] = $old_pic?->name;
                    $result['new'][$key]['name'] = null;
                }
            }
        }

        return $result;
    }

    // /**
    //  * Compare old and new values of a many-to-many relationship to determine changes.
    //  *
    //  * @param mixed $model
    //  * @param string $relation
    //  * @param array $newValues
    //  * @param array $oldValues
    //  * @return array
    //  */
    // public function compareRelationshipValues($model, string $relation, array $oldValues)
    // {
    //     $newValues = $model->$relation()->get()->pluck('id')->toArray();

    //     $added = array_diff($newValues, $oldValues);
    //     $removed = array_diff($oldValues, $newValues);

    //     if (!count($added) && !count($removed)) {
    //         return null;
    //     }

    //     return [
    //         $relation => [
    //             'added' => $added,
    //             'removed' => $removed,
    //         ]
    //     ];
    // }

    // public function addRelationshipValues($model, string $relation)
    // {
    //     $values = $model->$relation()->get()->pluck('id')->toArray();
    //     if (!count($values)) {
    //         return null;
    //     }

    //     return $values;
    // }
}