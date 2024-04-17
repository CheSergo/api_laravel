<?php
namespace App\Traits\Relations;

use App\Modules\Sites\Site;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Helpers\HFunctions;

/**
 * Trait HasSite
 * @package App\Traits\Relations
 * Модель имеет сайт
 */
trait HasSite {

    /**
     * @return mixed
     */
    public function site(): BelongsTo {
        return $this->belongsTo(Site::class, 'site_id', 'id');
    }

    /**
     * Scopes
     */
    public function scopeThisSite($query, $site = true) {
//        $admin = HFunctions::isRoleUser(request()->user(), ['admin']);
//        if ($admin && !$site) {
//            return $query;
//        } else {
            return $query->where('site_id', request()->user()->active_site_id); // id current site
//        }
    }
    
    public function scopeThisSiteFront($query) {
        return $query->where('site_id', request()->header('SiteID')); // id current site
    }

}