<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Cloudinary\Uploader as Cloudinary;

class Image extends Model
{

    protected $table = 'images';

    protected $fillable = [
        'image_url', 'thumb_url', 'img_public_id', 'thumb_public_id',
        'img_width', 'img_height', 'thumb_width', 'thumb_height', 'format',
        'original_filename'
    ];

    protected $hidden = [
        'img_public_id', 'thumb_public_id', 'imageable_id', 'imageable_type'
    ];

    // public function imageable()
    // {
    //     return $this->morphTo('App\Models\Porduct');
    // }


    public static function boot()
    {
        parent::boot();
        static::deleting(function ($image) {
            Cloudinary::destroy($image->img_public_id);
            Cloudinary::destroy($image->thumb_public_id);
        });
    }
}
