<?php

namespace App\Modules\Basket;

use Carbon\Carbon;
use App\Http\Controllers\Controller;

// Requests
use Illuminate\Http\Request;

// Helpers
use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

use App\Modules\Basket\BasketAdminController;

class Cleaner extends Controller
{
    protected $basketListService;

    public function __construct(BasketAdminController $basketListService)
    {
        $this->basketList = $basketListService->basketList;
    }

    public function janitor() {
        foreach ($this->basketList as $item) {
            $modelClass = $item['model'];
            $trashedItems = $modelClass::onlyTrashed()->get();
    
            foreach ($trashedItems as $trashedItem) {
                $deletedAt = $trashedItem->deleted_at;
                $sixMonthsAgo = Carbon::now()->subMonths(6);
    
                if ($deletedAt->lt($sixMonthsAgo)) {
                    $trashedItem->forceDelete();
                }
            }
        }
    }
}