<?php

namespace App\Http\Middleware;

use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Closure;
use Response;

class AuthenticateAdmin
{
    public function handle($request, Closure $next)
    {
        if (!auth()->guard('admin')->check()) {
            return Response::json([
                'successful' => '0',
                'status' => '02',
                'message' => 'Unauthorized Admin'
            ], 401);
        }

        return $next($request);
    }

    // public function handle($request, Closure $next)
    // {

    //     try {
    //         $user = JWTAuth::parseToken()->authenticate();
    //         $token = JWTAuth::getToken();
    //         $payload = JWTAuth::getPayload($token)->toArray();
    //         if ($payload['type'] != 'admin') {
    //             return response()->json([
    //                 'successful' => '0',
    //                 'status'     => '02',
    //                 'message'    => 'Not authorized'
    //             ], 401);
    //         }
    //     } catch (Exception $e) {
    //         if ($e instanceof TokenInvalidException) {
    //             return response()->json(['status' => 'Token is Invalid']);
    //         } else if ($e instanceof TokenExpiredException) {
    //             return response()->json(['status' => 'Token is Expired']);
    //         } else {
    //             return response()->json(['status' => 'Authorization Token not found']);
    //         }
    //     }

    //     return $next($request);
    // }
}
