<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'is_finished', 'odometer', 'notes', 'retail_value'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function images()
    {
        return $this->morphMany('App\Models\Image', 'imageable');
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
