<?php
namespace App\Traits\Utils;

trait TrackAttributeChanges
{
    /**
     * Get the attributes that have been changed since the model was last retrieved or saved.
     *
     * @return array
     */
    public function getChangedAttributes()
    {
        $changed = [];
        foreach ($this->getAttributes() as $key => $value) {
            if ($this->isDirty($key)) {
                $changed[$key] = $value;
            }
        }
        return $changed;
    }

}