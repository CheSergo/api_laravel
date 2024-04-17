<?php

namespace App\Modules\Sites\SocialNetworks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\Relations\HasSite;

class SocialNetwork extends Model
{
    use HasFactory, SoftDeletes, HasSite;

    protected $table = 'social_networks';

    protected $fillable = [
        'code', 'link', 'icon'
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
    ];

    
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }
}
