<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Services\AttachImagesToModel;
use Tymon\JWTAuth\JWTAuth;

// use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{


    protected $auth;

    public function __construct(JWTAuth $auth)
    {
        $this->auth = $auth;
    }
    //






    // activate or block user
    public function changeStateOfUser($id, Request $request)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User Not Found !'
            ], 404);
        }

        try {
            $this->validate($request, [
                'state' => [
                    'required', 'string', Rule::in(['blocked', 'pending', 'active']),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'successful' => '0',
                'status'  => '02',
                'error' => 'Invalid data: ' . $e
            ], 400);
        }


        $user->account_state = $request->state;

        if ($user->update()) {
            return response()->json([
                'meessage' => $user->name . '\'s account has been Successfully changed to' . ' ' . $request->state
            ], 200);
        } else {
            return response()->json([
                'meessage' => ' Something Went Wrong, Try agian'
            ], 500);
        }
    }




    // adding addAdvertisment by admin
    public function addAdvertisment(Request $request)
    {
        $this->validate($request, [
            'link'     => 'required|string',
            'image' => 'required|image|mimes:jpeg,bmp,jpg,png|between:1,6000|dimensions:min_width=1024,max_height=1024'
        ]);

        $advertisment = new Ad();
        $advertisment->link = $request->link;
        if ($advertisment->save()) (new AttachImagesToModel($request->image, $advertisment))->saveImages();

        return response()->json([
            'message' => 'Advertisment Added Successfully!',
        ], 200);
    }



    public function getLastFiveAds()
    {

        $advertisments = Ad::with('images')->take(5)->get();
        if (count($advertisments) < 1) {

            return response()->json([
                'message' => 'No Advertisments added yet!'
            ], 200);
        }

        return response()->json([
            'message' => 'Here is ads!',
            'advertisment' => $advertisments
        ], 200);
    }


    // make vehicle as fearured
    public function setAsFeatured($id)
    {

        $vehicle = Vehicle::findOrFail($id);

        if (!$vehicle) {
            return response()->json([
                'message' => 'Vehicle Not Found!',
            ], 404);
        }

        $vehicle->featured = true;

        if ($vehicle->update()) {
            return response()->json([
                'message' => 'Vehicle Updated Successfully',
            ], 200);
        }
    }
}
