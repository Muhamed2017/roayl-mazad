<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use mysql_xdevapi\Exception;
use Illuminate\Validation\Validator;
use Response;
use Hash;
use Illuminate\Support\Facades\Austh;
use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;
use App\Support\Services\AddImagesToEntity;
use App\Events\UserCreated;
use App\Models\Admin;
use App\Support\Services\AttachImagesToModel;
use Illuminate\Foundation\Events\Dispatchable;
use Carbon\Carbon;

class AuthController extends Controller
{


    protected $auth;

    public function __construct(JWTAuth $auth)
    {
        $this->auth = $auth;
    }


    public function register(Request $request)
    {
        $guard = $request->route()->getName();

        try {
            $this->validate($request, [
                'name' => 'nullable|string|max:255',
                'phone' => $guard == 'user' ? 'required|string|max:255' : 'nullable|string|max:255',
                'address' => 'nullable|string|max:250',
                'email' => $guard == 'user' ? 'required|email|max:255|unique:users,email' : 'required|email|max:255|unique:admins,email',
                'password' => 'required|confirmed|min:6|max:255',
                'avatar' => 'nullable|image|mimes:jpeg,bmp,jpg,png|between:1,6000'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'successful' => '0',
                'status'  => '02',
                'error' => 'Invalid data: ' . $e
            ], 400);
        }

        $user_input = $request->only(
            'name',
            'phone',
            'email',
            'address',
            'password',
        );
        $admin_input = $request->only(
            'name',
            'email',
            'password',
        );

        $user_input['password'] = bcrypt($request->get('password'));
        $user_input['active'] = 0;
        $user_input['verification_token'] = Str::random(64);


        $admin_input['password'] = bcrypt($request->get('password'));
        $admin_input['active'] = 1;
        $admin_input['verification_token'] = Str::random(64);

        if ($guard == 'user') {

            $user = new User($user_input);
        } else {

            $user = new Admin($admin_input);
        }

        if ($user->save()) {

            if ($request->hasFile('avatar')) (new AttachImagesToModel($request->avatar, $user))->saveImages();
            // (new AddImagesToEntity($request->avatar, $user, ["width" => 600]))->execute();


            $token = auth($guard)->login($user);

            $tokenExpiresAt = \Carbon\Carbon::now()->addMinutes(auth($guard)->factory()->getTTL() * 1)->toDateTimeString();

            return response()->json([
                'successful' => '1',
                'status' => '01',
                'message' => 'Your account has been registered successfully',
                'token_type' => 'Bearer',
                'Bearer_token' => $token,
                'expires_at' => $tokenExpiresAt,
                'user' => $user,
                // 'state' => $user->account_state && $guard == 'user' ?? 'pending',
                'type' => $guard
            ], 200);
        }

        return response()->json([
            'successful' => '0',
            'status'  => '02',
            'error' => 'failed, please try again'
        ], 500);
    }


    public function login(Request $request)
    {

        $guard = $request->route()->getName();

        $request->validate([
            'email' => 'required|email|max:250',
            'password' => 'required',
        ]);

        // $user = $this->getUser($request);

        if ($guard == 'user') {
            $user = $this->getUser($request);
        } else {
            $user = $this->getAdmin($request);
        }
        // Check if user exist
        if (!$user) {
            return response()->json([
                'successful' => '0',
                'status' => '02',
                'message' => 'invalid email, username or password'
            ], 422);
        }

        $creds = [
            "email" => $user->email,
            'password' => $request->get('password')
        ];

        // try login
        try {
            if (!$token = auth($guard)->attempt($creds, ['exp' => Carbon::now()->addDays(70)->timestamp])) {
                return response()->json([
                    'successful' => '0',
                    'status' => '02',
                    'error'  => 'invalid email or passwords',
                    'token' => auth($guard)->attempt($creds, ['exp' => Carbon::now()->addDays(70)->timestamp])
                ], 200);
            }
        } catch (JWTException $e) {
            return response()->json([
                'successful' => '0',
                'status' => '02',
                'error' => 'could not create user token, please try again'
            ], 500);
        }

        $tokenExpiresAt = Carbon::now()->addMinutes(auth($guard)->factory()->getTTL())->toDateTimeString();

        return response()->json([
            'successful' => '1',
            'status' => '01',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $token,
                'expires_at' => $tokenExpiresAt,
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'avatar' => $user->avatar->img_url ?? '',
                'state' => $user->account_state,
                'type' => $guard
            ]
        ], 200);
    }


    // get the user
    private function getUser(Request $request)
    {
        $user = null;
        if (!empty($request->email)) {
            $user = User::with('images')->where('email', $request->email)->first();
        } else if (!empty($request->mobile)) {
            $user = User::with('images')->where('mobile', $request->mobile)->first();
        }
        return $user;
    }


    private function getAdmin(Request $request)
    {
        $admin = null;

        if (!empty($request->email)) {
            $admin = Admin::with('images')->where('email', $request->email)->first();
        }
        return $admin;
    }
}
