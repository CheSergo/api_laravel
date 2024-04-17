<?php

namespace App\Modules\Sites\PosAppeals;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Media
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PosVariant extends Model  implements HasMedia
{
    use HasFactory, SoftDeletes;
    use InteractsWithMedia;

    protected $table = 'pos_variants';

    protected $fillable = [
        'title', 'code'
    ];

    /**
     * Media
     */
    public function registerMediaCollections(): void {
        $this->addMediaCollection('pos_variant_posters')->useDisk('pos_variant_posters');
    }
    
}