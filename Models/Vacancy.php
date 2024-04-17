<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\HString;
use App\Helpers\HFunctions;
use Carbon\Carbon;
use App\Http\Filters\Common\Filterable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\Common\HasDocuments;

/**
 * Class JobOpening
 * @package App\Models\Common
 * Вакансии - конкурс на вакансии
 */
class Vacancy extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, Filterable, HasDocuments;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title', 'body',
    ];

    protected static $logAttributes = ['title', 'site_id', 'begin_at', 'end_at', 'is_published', 'published_at'];
    protected static $logName = 'job_opening';

    /**
     * @var string[]
     */
    protected $casts = [
        'published_at' => 'datetime:Y-m-d h:i:s',
        'is_published' => 'boolean',
        'body' => 'array',
    ];

    /**
     * Преобразователи
     */
    public function setTitleAttribute($value) {
        $this->attributes['title'] = HString::rus_quot($value);
    }
    public function setPublishedAtAttribute($value) {

        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function setBeginAtAttribute($value) {
        if (isset($value)) $this->attributes['begin_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
        else $this->attributes['begin_at'] = null;
    }
    public function setEndAtAttribute($value) {
        if (isset($value))   $this->attributes['end_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
        else $this->attributes['end_at'] = null;
    }
    /**
     * @param $query
     * @return mixed
     */
    public function scopeThisSite($query)
    {
        return $query->where('site_id', config('app.site_id')); // id current site
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    public function user()
    {
        return $this->belongsTo('\App\Models\User', 'user_id', 'id');
    }

    public function site()
    {
        return $this->belongsTo('\App\Models\Common\Site', 'site_id', 'id');
    }

    /**
     * Запись в журнал событий (для связей)
     * @param $log_name - название
     * @param $description - описание (created, updated, deleted)
     * @param $properties - свойства
     * @return mixed
     */
    public function saveActivity($log_name, $description, $properties) {

        return activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->inLog($log_name)
            ->log($description);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
