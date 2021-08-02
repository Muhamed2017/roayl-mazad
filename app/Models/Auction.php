<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'firebase_id', 'vehicle_title', 'lister_name', 'lister_id', 'sell_type', 'final_price',
        'vehicle_start_date',
        'vehicle_start_time',  'auction_owner', 'auction_owner_id', 'reatail_value'
    ];

    protected $casts = [
        // 'vehicle_start_data' => 'date:hh:mm'
    ];

    public function vehicle()
    {
        return   $this->belongsTo('App\Models\Vehicle');
    }
}
