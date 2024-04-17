<?php

namespace App\Modules\Sites\PosAppeals;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Relations
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Models
use App\Modules\Sites\PosAppeals\PosVariant;

class PosAppeal extends Model
{
    use HasFactory, SoftDeletes;

    protected $with = ['pos_variant'];

    protected $table = 'pos_appeals';

    protected $fillable = [
        'title', 'code', 'description', 'variant'
    ];

    public function pos_variant(): BelongsTo {
        return $this->belongsTo(PosVariant::class);
    }

}