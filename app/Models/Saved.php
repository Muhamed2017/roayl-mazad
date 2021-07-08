<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saved extends Model
{
    use HasFactory;

    protected $fillable = ['vehicle_id', 'user_id'];

    protected $table = 'saved';

    protected $hidden = [
        'pivot'
    ];
}
