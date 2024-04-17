<?php
namespace App\Modules\Banners\FrontComponents;

use App\Http\Controllers\Controller;

// Models
use App\Modules\Banners\Banner;

//Filters
use App\Modules\Banners\BannersFilter;

class Banners extends Controller 
{
    public $model = Banner::class;
    private $filter;

    public function __construct(BannersFilter $filter) {
        $this->filter = $filter;
    }

    public function getByArea($area) {
        return Banner::filter($this->filter)->published()->orderBy('sort', 'ASC')->orderBy('title', 'ASC')->where('area', $area)->with('media')->get();
    }

}
