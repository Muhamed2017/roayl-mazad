<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    // public $status = ['new', 'pending', 'confirmed', 'canceled', 'no response'];

    /*
    |--------------------------------------------------------------------------
    | response arrays section
    |--------------------------------------------------------------------------
    */

    // TODO: mode this section to Exceptions Class
    // errors arrays
    public $authorizationErr = [
        'successful' => '0',
        'status' => '02',
        'error' => ['You do not have the required role or permission to view this section.']
    ];

    public $failedErr = [
        'successful' => '0',
        'status'  => '02',
        'error' => ['failed, please try again']
    ];

    public $entityNotFoundErr = [
        'successful' => '0',
        'status' => '02',
        'message' => 'entity not found'
    ];


    // normal arrays
    public $entityCreatedSucc = [
        'successful' => '1',
        'status' => '01',
        'message' => 'entity created successfully'
    ];

    public $entityUpdateSucc = [
        'successful' => '1',
        'status' => '01',
        'message' => 'entity updated successfully'
    ];

    public $entityDeletedSucc = [
        'successful' => '1',
        'status' => '01',
        'message' => 'entity deleted successfully'
    ];

    public function successfullUpdate($entity)
    {
        return [
            'successful' => '1',
            'status' => '01',
            'message' => $entity . ' updated successfully'
        ];
    }
}
