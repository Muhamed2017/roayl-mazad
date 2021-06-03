<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;

    protected $table = 'ads';

    protected $fillable = ['link'];


    public function images()
    {
        return $this->morphOne('App\Models\Image', 'imageable');
    }







    public static function boot()
    {
        // schema::defaultStringLength(191);
        parent::boot();
        static::deleting(function ($ad) {

            if (count($ad->images) > 0) {
                foreach ($ad->images as $image) {
                    $image->delete();
                }
            }
        });
    }
}
