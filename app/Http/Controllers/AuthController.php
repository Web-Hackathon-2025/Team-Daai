<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMessage;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    use ResponseAPI;
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        if (!$token = auth('api')->attempt($request->only('email', 'password'))) {
            return $this->error(ResponseMessage::INVALID_LOGIN);
        }

        $user = auth('api')->user()->load(['business','roles','roles.permissions']);
        $user->roles->each->makeHidden('pivot');

        if (
            !$user->business &&
            !$user->roles->contains('name', 'Super Admin')
        ) {
            auth('api')->logout();
            JWTAuth::invalidate($token);
            return $this->error(ResponseMessage::INVALID_COMPANY_ATTACHED);
        }

        $subscriptionEnd = isset($user->business) ? $user->business->subscription_end_date : null;

        if (isset($subscriptionEnd) && Carbon::now()->gt(Carbon::parse($subscriptionEnd))) {
            auth('api')->logout();
            JWTAuth::invalidate($token);
            return $this->error(ResponseMessage::SUBSCRIPTION_EXPIRED);
        }
        $user->token = $token;

        return $this->success($user, ResponseMessage::LOGIN, 200);
    }

    public function refresh()
    {
        try {
            $newToken = auth('api')->refresh(); // get new token
            return $this->success($newToken);
        } catch (TokenExpiredException $e) {
            return $this->error('Token expired, please login again');
        } catch (TokenInvalidException $e) {
            return $this->error('Invalid token');
        } catch (JWTException $e) {
            return $this->error('Token not provided');
        }
    }

    public function logout()
    {
        auth('api')->logout();
        return $this->success(null, ResponseMessage::LOGOUT, 200);
    }


}
