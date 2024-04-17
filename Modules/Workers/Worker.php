<?php
namespace App\Modules\Workers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
// use Illuminate\Database\Eloquent\Relations\HasMany;

// Helpers
use Carbon\Carbon, Str;
// use App\Helpers\HFunctions;
use App\Helpers\HString;

// Models
use App\Modules\Articles\Article;
use App\Modules\Departments\Department;
// use App\Modules\Sites\SocialNetworks\SocialNetwork;

// Traits
use App\Traits\Relations\HasDocuments;
use App\Traits\Relations\HasAuthors;
use App\Traits\Relations\HasSite;
use App\Traits\Relations\HasSocialNetworks;
use App\Traits\Common\GetSearchParams;
use App\Traits\Utils\Search;

// Filters
use App\Http\Filters\Filterable;

// Media
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Traits\Actions\InteractsWithCustomMedia;

class Worker extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, InteractsWithCustomMedia, SoftDeletes, Filterable;
    use HasDocuments, HasAuthors, HasSite, HasSocialNetworks, GetSearchParams, Search;

    protected $appends = [
        /*'table_name',*/
        'search_params',
    ];

//    public function getTableNameAttribute()
//    {
//        return "workers";
//    }

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s',
        'is_published' => 'boolean',
        'credentials' => 'array',
        'biography' => 'array',
    ];

    protected $with = ['media', 'social_networks'];
  
    /**
     * Mutators
     */
    public static function boot() {
        parent::boot();
        self::deleted(function($worker) {
            if($worker->isForceDeleting()) {
                $worker->documents()->detach();
                $worker->departments()->detach();
                $worker->articles()->detach();
            }
        });
    }

    public function setPositionAttribute($value)  {
        $this->attributes['position'] = Str::ucfirst($value);
    }

    public function setPublishedAtAttribute($value)  {
        $this->attributes['published_at'] =  Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getPublishedAtAttribute($value) {
        return Carbon::parse($value)->toISOString();
    }

    public function getFioAttribute()  {
        return trim("{$this->surname} {$this->name} {$this->second_name}");
    }

    public function getFullNameAttribute()  {
        return trim("{$this->name} {$this->second_name}");
    }

    public function getPositionAttribute()  {
        return Str::ucfirst($this->attributes['position']);
    }

    /**
     * Scopes
     */
    public function scopePublished($query) {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', date("Y-m-d H:i:s"));
    }

    public function scopeSearchNameWorkers($query, string $fio)  {
        return $query->where(function ($q) use ($fio) {
            $q->orWhere('name', 'like', "%$fio%")
                ->orWhere('surname', 'like', "%$fio%")
                ->orWhere('second_name', 'like', "%$fio%");
        });
    }

    // Methonds
    public function sync_with_sort($model, string $relation, array $items, string $sort_name) {
        $model->$relation()->detach();
        if($items) {
            foreach($items as $index => $item) {
                $model->$relation()->attach($item, [$sort_name => $index+1]);
            }
        }
    }
    
    /**
     * Relations
     */
    public function departments(): MorphToMany {
        return $this->morphedByMany(Department::class, 'model', 'model_has_workers', 'worker_id', 'model_id');
    }

    public function articles(): MorphToMany {
        return $this->morphedByMany(Article::class, 'model', 'model_has_workers', 'worker_id', 'model_id');
    }

     /**
     * @return mixed
     * Определение поля персоны по имени
     */
    public function getGenderAttribute() {
        $name = HString::transliterate($this->name);
        $country = \GenderDetector\Country::RUSSIA;
        $male = \GenderDetector\Gender::MALE;
        $female = \GenderDetector\Gender::FEMALE;
        $genderDetector = new \GenderDetector\GenderDetector();

        if (!$genderDetector->detect($name, $country)) {
            $array = app('genders');
            if (array_key_exists($name, $array)) {
                $gender = $array[$name] == 'female' ? $female : $male;
                $genderDetector->setUnknownGender($gender);
            }
        }
        return $genderDetector->detect($name, $country);
    }
 
    /**
     * Медиа
     */
    public function registerMediaCollections(): void {
        $this->addMediaCollection('worker_photos')->useDisk('worker_photos');
    }
}
