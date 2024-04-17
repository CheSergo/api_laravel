<?php
namespace App\Traits\Attributes;

use Carbon\Carbon;

trait PublishedAt {

    public function setPublishedAtAttribute($value) {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

}