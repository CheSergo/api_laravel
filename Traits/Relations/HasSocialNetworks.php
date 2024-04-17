<?php
namespace App\Traits\Relations;

use App\Modules\SocialNetworks\SocialNetwork;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasSocialNetworks {

    /**
     * @return mixed
     */
    public function social_networks(): BelongsToMany
    {
        return $this->morphToMany(SocialNetwork::class, 'model', 'model_has_social_network', 'model_id', 'social_network_id');
    }

}