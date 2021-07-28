<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use CloudinaryLabs\CloudinaryLaravel\MediaAlly;


class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, MediaAlly;
    // use MediaAlly,

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'phone',
        'country',
        'city',
        'dob',
        'address',
        'email',
        'password',
        'account_state'
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


    protected $appends = ['avatar', 'id_file'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public $imgFolderPath = [
        "image" => "Users/Images/",
        "thumb" => "Users/Thumbnails/"
    ];



    public function vehicles()
    {
        return $this->hasMany('App\Models\Vehicle');
    }

    public function savedVehicles()
    {
        return $this->belongsToMany('App\Models\Saved');
    }


    public function getAvatarAttribute()
    {
        return $this->images != null ? $this->images->first() : '';
    }

    public function getIdFileAttribute()
    {
        return $this->images != null ? $this->images()->OrderBy('id', 'desc')->first() : '';
    }

    public function images()
    {
        return $this->morphMany('App\Models\Image', 'imageable');
    }



    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
