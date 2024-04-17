<?php
namespace App\Traits\Utils;

trait TrackRelationshipChanges
{
    /**
     * Get the values of a many-to-many relationship.
     *
     * @param mixed $model
     * @param string $relation
     * @return array
     */
    public function getRelationshipValues($model, string $relation)
    {
        return $model->$relation()->get()->pluck('id')->toArray();
    }

    /**
     * Compare old and new values of a many-to-many relationship to determine changes.
     *
     * @param mixed $model
     * @param string $relation
     * @param array $newValues
     * @param array $oldValues
     * @return array
     */
    public function compareRelationshipValues($model, string $relation, array $oldValues)
    {
        $newValues = $model->$relation()->get()->pluck('id')->toArray();

        $added = array_diff($newValues, $oldValues);
        $removed = array_diff($oldValues, $newValues);

        if (!count($added) && !count($removed)) {
            return null;
        }

        return [
            $relation => [
                'added' => $added,
                'removed' => $removed,
            ]
        ];
    }

    public function addRelationshipValues($model, string $relation)
    {
        $values = $model->$relation()->get()->pluck('id')->toArray();
        if (!count($values)) {
            return null;
        }

        return [
            $relation => $values
        ];
    }
}