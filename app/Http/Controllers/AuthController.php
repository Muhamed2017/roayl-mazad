<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
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
use App\Mail\SendMail;

use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

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

        $this->validate($request, [
            'name' => 'nullable|string|max:255',
            'phone' => $guard == 'user' ? 'required|string|unique:users,phone|max:255' : 'nullable|string|max:255',
            'address' => 'nullable|string|max:250',
            'email' => $guard == 'user' ? 'required|email|max:255|unique:users,email' : 'required|email|max:255|unique:admins,email',
            'password' => 'required|confirmed|min:6|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,bmp,jpg,png|between:1,6000',
            'id_file' => 'nullable|image|mimes:jpeg,bmp,jpg,png|between:1,6000',
            'country' => 'required|string|max:255',
            'city' => "required|string|max:255",
            'dob' => 'required|date_format:d-m-Y'
        ]);

        $user_input = $request->only(
            'name',
            'phone',
            'email',
            'address',
            'password',
            'country',
            'city',
            'dob',
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
            if ($request->hasFile('avatar')) {
                $user->attachMedia($request->avatar);
            }
            if ($request->hasFile('id_file')) {
                $user->attachMedia($request->id_file);
            }



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
            $user = User::where('email', $request->email)->first();
        } else if (!empty($request->mobile)) {
            $user = User::where('mobile', $request->mobile)->first();
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




    //forget password
    public function sendPasswordResetEmail(Request $request)
    {
        // If email does not exist
        if (!$this->validEmail($request->email)) {
            return response()->json([
                'message' => 'Email does not exist.'
            ], HttpFoundationResponse::HTTP_NOT_FOUND);
        } else {
            // If email exists
            $this->sendMail($request->email);
            return response()->json([
                'message' => 'Check your inbox, we have sent a link to reset email.'
            ], HttpFoundationResponse::HTTP_OK);
        }
    }

    public function sendMail($email)
    {
        $token = $this->generateToken($email);
        Mail::to($email)->send(new SendMail($token));
    }

    public function validEmail($email)
    {
        return !!User::where('email', $email)->first();
    }

    public function generateToken($email)
    {
        $isOtherToken = DB::table('recover_password')->where('email', $email)->first();

        if ($isOtherToken) {
            return $isOtherToken->token;
        }

        $token = Str::random(80);;
        $this->storeToken($token, $email);
        return $token;
    }

    public function storeToken($token, $email)
    {
        DB::table('recover_password')->insert([
            'email' => $email,
            'token' => $token,
            'created' => Carbon::now()
        ]);
    }

    public function myProfile()
    {
        $user = auth('user')->user();

        $profile = User::findOrFail($user->id);
        $assets = $profile->fetchAllMedia();

        if (!$profile) {
            return response()->json(['message' => 'user not exist'], 404);
        }

        return response()->json([
            // 'profile' => $profile,
            'id' => $profile->id,
            'name' => $profile->name,
            'phone' => $profile->phone,
            'email' => $profile->email,
            'city' => $profile->city,
            'dob' => $profile->dob,
            'address' => $profile->address,
            'account_status' => $profile->account_status,
            'dob' => $profile->dob,
            'avatar' => $assets[0]->file_url ?? "",
            'id_file' => $assets[1]->file_url ?? "",
            'my_added_car' => $user->vehicles

        ], 200);
    }
}
