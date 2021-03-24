<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary API configuration
    |--------------------------------------------------------------------------
    |
    | Before using Cloudinary you need to register and get some detail
    | to fill in below, please visit cloudinary.com.
    |
    */

    'cloudName'  => env('CLOUDINARY_NAME', 'azharuniversity'),
    'baseUrl'    => env('CLOUDINARY_BASE_URL', 'http://res.cloudinary.com/azharuniversity'),
    'secureUrl'  => env('CLOUDINARY_SECURE_URL', 'https://res.cloudinary.com/azharuniversity'),
    'apiBaseUrl' => env('CLOUDINARY_API_BASE_URL', 'https://api.cloudinary.com/v1_1/azharuniversity'),
    'apiKey'     => env('CLOUDINARY_API_KEY', 423572895637956),
    'apiSecret'  => env('CLOUDINARY_API_SECRET', '7C9vL_lW4aO6uTZe5vKoe76nvcE'),

    'scaling'    => [
        'format' => 'png',
        'width'  => 150,
        'height' => 150,
        'crop'   => 'fit',
        'effect' => null
    ],

];
