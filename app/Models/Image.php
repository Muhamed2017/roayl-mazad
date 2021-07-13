<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use \Cloudinary\Uploader as Cloudinary;

class Image extends Model
{

    protected $table = 'images';

    protected $fillable = [
        'image_url',
    ];

    protected $hidden = [
        'id', 'img_width', 'img_height', 'format',
        'imageable_id', 'imageable_type', 'img_bytes',
        'original_filename', 'created_at', 'updated_at'

    ];

    public function imageable()
    {
        return $this->morphTo('App\Models\Vehicle');
    }



    public static function boot()
    {
        parent::boot();
        static::deleting(function ($image) {
            // Cloudinary::destroy($image->img_public_id);
            // Cloudinary::destroy($image->thumb_public_id);
            // $image->destroy
        });
    }
}
