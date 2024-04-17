<?php
namespace App\Traits\Common;

use App\Modules\Sections\Section;

/**
 * Trait WithSection
 * @package App\Traits\Common
 */
trait WithSection {

    /**
     * @return mixed
     */
    public function section($order = 2) {
        $arr = explode('/', request()->path());
        if (!isset($arr[count($arr) - $order])) {
            abort(404);
        }
        $slug = $arr[count($arr) - $order];
        return Section::thisSite()->published()->with('childs', function ($query) {
            $query->thisSite()->published()->with('childs');
        })->with('parent', function ($query) {
            $query->thisSite()->published()->with('childs');
        })->where('slug', $slug)->firstOrFail();
    }

    /**
     * @param $section
     * @return null
     */
    public function childs($section) {
        $childs = null;
        if ($section->childs && count($section->childs)) {
            $childs = $section->childs;
        } else {
            if ($section->parent && $section->parent->childs && count($section->parent->childs) > 1) {
                $childs = $section->parent->childs;
            } else {
                $childs = null;
            }
        }

        return $childs;
    }

}