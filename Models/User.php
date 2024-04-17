<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\PasswordReset;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Spatie\Permission\Traits\HasRoles;

// Models
use App\Modules\Sites\Site;
use App\Modules\Sources\Source;

// Filters
use App\Http\Filters\Filterable;

// Traits
use App\Traits\Relations\HasSources;
use App\Traits\Relations\HasRoles;
use App\Traits\Relations\HasAbilities;

// Logs
//use Spatie\Activitylog\Traits\LogsActivity;
//use Spatie\Activitylog\LogOptions;

class User extends Authenticatable //implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, Filterable;
    use HasRoles, HasSources, HasApiTokens, HasAbilities, HasSources/*, LogsActivity*/;

    // protected $with = ['sites'];

    public $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'surname',
        'name',
        'second_name',
        'email',
        'password',
        'password_changed_at',
        'active_site_id',
        'remember_token',
        'last_active_at',
        'logout_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Mutators
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    public function setSurnameAttribute($value)
    {
        $this->attributes['surname'] = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    public function setSecondNameAttribute($value)
    {
        $this->attributes['second_name'] = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @param $source
     * @return bool
     * Проверяет привязана ли организация к пользователю
     */
    public function is_source($source) {
        if (!count($this->sources) || !in_array($source, $this->sources->pluck('id')->toArray()))
            return false;

        return true;
    }

    /**
     * @return mixed
     */
    public function list_iogv() {
        return Source::orderBy('title', 'asc')->where('is_government', 1)->pluck('title', 'id')->all();
    }

    public function getFioAttribute() {
        return trim("{$this->surname} {$this->name} {$this->second_name}");
    }
    public function getFullNameAttribute() {
        return trim("{$this->name} {$this->second_name}");
    }
    public function getInitialsAttribute() {
        $str = trim($this->name.' '.$this->second_name);
        foreach (explode(' ',$str) as $key=>$value)
        {
            $arr["$key"] = mb_strtoupper(mb_substr(trim($value),0,1));
        }
        return $this->surname.' '.implode('.',$arr).'.';
    }
    public function getAuthorAttribute() {
        if (isset($this->surname) && isset($this->name))
        {
            if (isset($this->second_name)) return $this->surname . ' ' . $this->name . ' ' . $this->second_name;
            else return $this->surname . ' ' . $this->name . ' ' ;
        }
        else return $this->email;
    }
    

    // ПРОВЕРЕННЫЕ СВЯЗИ
    public function sites()
    {
        return $this->belongsToMany(Site::class, 'user_sites')->withTimestamps();
    }

    public function active_site()
    {
        return $this->hasOne(Site::class, 'id', 'active_site_id');
    }

    // public function activeSite($id)
    // {
        // return $this->sites()->where('');
    // }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordReset($token, $this->email));
    }

    /**
     * Logs
     */

//    public function getActivitylogOptions(): LogOptions
//    {
//        return LogOptions::defaults()
//            ->logOnly($this->fillable)
//            ->logOnlyDirty();
//    }
}
