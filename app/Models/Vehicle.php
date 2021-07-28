<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use CloudinaryLabs\CloudinaryLaravel\MediaAlly;

class Vehicle extends Model
{
    use HasFactory, MediaAlly;

    protected $table = 'vehicle';

    protected $casts = [
        // 'odometer' => 'array',
        'special_notes' => 'array',
    ];


    protected $fillable = [
        'user_id', 'listed_by', 'vehicle_title', 'vehicle_vin', 'primary_damage',
        'transmission', 'fuel', 'engine_type', 'vat_added', 'sell_type', 'drive', 'keys',
        'odometer', 'special_notes', 'retail_value', 'published', 'featured', 'highlights', "selender",
        'category', 'color', 'model', 'year', 'company', 'starts_at_date',

    ];
    protected $appends = ['photo'];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }


    public function images()
    {
        return $this->morphMany('App\Models\Image', 'imageable');
    }

    public function getPhotoAttribute()
    {
        // return $this->fetchFirstMedia() != null ? $this->images->first() : '';
        return $this->fetchFirstMedia()->file_url ?? "";
    }


    public function saves()
    {
        return $this->belongsToMany('App\Models\Saved');
    }

    public function scopeTermSearch(Builder $query, $term): Builder
    {
        return $query->where('vehicle_title', 'LIKE', "%" . $term . "%")
            ->orWhere('color', 'LIKE', "%" . $term . "%")
            ->orWhere('transmission', 'LIKE', "%" . $term . "%")
            ->orWhere('engine_type', 'LIKE', "%" . $term . "%")
            ->orWhere('model', 'LIKE', "%" . $term . "%")
            ->orWhere('primary_damage', 'LIKE', "%" . $term . "%")
            ->orWhere('category', 'LIKE', "%" . $term . "%");
    }

    public static function boot()
    {
        // schema::defaultStringLength(191);
        parent::boot();
        static::deleting(function ($vehicle) {

            if (count($vehicle->images) > 0) {
                foreach ($vehicle->images as $image) {
                    $image->delete();
                }
            }
        });
    }
}
