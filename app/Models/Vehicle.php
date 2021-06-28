<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicle';

    protected $casts = [
        'odometer' => 'array',
        'notes' => 'array',
    ];


    protected $fillable = [
        'user_id', 'listed_by', 'vehicle_title', 'vehicle_vin', 'vehicle_vrn', 'primary_damage',
        'secondary_damage', 'category', 'color', 'transmission', 'fuel', 'engine_type', 'vat_added',
        'body_style', 'sell_type', 'drive', 'keys', 'state', 'model', 'year', 'company', 'starts_at_date',
        'is_finished', 'odometer', 'notes', 'retail_value', 'published', 'featured'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }


    public function images()
    {
        return $this->morphMany('App\Models\Image', 'imageable');
    }


    public function scopeTermSearch(Builder $query, $term): Builder
    {
        return $query->where('vehicle_title', 'LIKE', "%" . $term . "%")
            ->orWhere('color', 'LIKE', "%" . $term . "%")
            ->orWhere('transmission', 'LIKE', "%" . $term . "%")
            ->orWhere('engine_type', 'LIKE', "%" . $term . "%")
            ->orWhere('body_style', 'LIKE', "%" . $term . "%")
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
