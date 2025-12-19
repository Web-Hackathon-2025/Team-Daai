<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMessage;
use App\Traits\ResponseAPI;
use App\Traits\LogsActivity;
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
    use ResponseAPI, LogsActivity;

    /**
     * @OA\Get(
     *     path="/api/hello-world",
     *     tags={"Authentication"},
     *     summary="Test endpoint",
     *     description="Simple endpoint to test API connectivity",
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Hello, World!")
     *         )
     *     )
     * )
     */
    public function helloWorld(Request $request)
    {
        return $this->success('Hello World endpoint reached successfully.', ['message' => 'Hello, World!'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="User login",
     *     description="Authenticate user and return JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="Success", type="boolean", example=true),
     *             @OA\Property(property="Status", type="integer", example=200),
     *             @OA\Property(property="Message", type="string", example="Login successfully"),
     *             @OA\Property(property="Data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="token", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request)
    {
        // dd('here ligcn', $request->all());
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return $this->error(ResponseMessage::INVALID_LOGIN);
            }
        } catch (JWTException $e) {
            return $this->error('Could not create token');
        }

        // Retrieve the Eloquent user instance from the token
        try {
            $user = JWTAuth::setToken($token)->toUser();
        } catch (\Exception $e) {
            // invalidate token if anything goes wrong
            JWTAuth::invalidate($token);
            return $this->error(ResponseMessage::INVALID_LOGIN);
        }

        // ensure $user is an Eloquent model before calling load()
        if ($user) {
            $user->load(['business', 'roles', 'roles.permissions']);
            $user->roles->each->makeHidden('pivot');
        }

        if (
            !$user ||
            (!$user->business && !$user->roles->contains('name', 'Super Admin'))
        ) {
            JWTAuth::invalidate($token);
            return $this->error(ResponseMessage::INVALID_COMPANY_ATTACHED);
        }

        $subscriptionEnd = isset($user->business) ? $user->business->subscription_end_date : null;

        if (isset($subscriptionEnd) && Carbon::now()->gt(Carbon::parse($subscriptionEnd))) {
            JWTAuth::invalidate($token);
            return $this->error(ResponseMessage::SUBSCRIPTION_EXPIRED);
        }
        $user->token = $token;

        // Log activity
        $this->logAuthActivity(
            'Login',
            "User '{$user->name}' logged in successfully",
            $user
        );

        return $this->success(ResponseMessage::LOGIN, $user, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/refresh",
     *     tags={"Authentication"},
     *     summary="Refresh JWT token",
     *     description="Get a new JWT token using existing valid token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="Success", type="boolean", example=true),
     *             @OA\Property(property="Data", type="string", example="new.jwt.token")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Token expired or invalid")
     * )
     */
    public function refresh()
    {
        try {
            $newToken = auth('api')->refresh(); // get new token

            return $this->success('Token refreshed successfully', ['token' => $newToken]);
        } catch (TokenExpiredException $e) {
            return $this->error('Token expired, please login again');
        } catch (TokenInvalidException $e) {
            return $this->error('Invalid token');
        } catch (JWTException $e) {
            return $this->error('Token not provided');
        }
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Authentication"},
     *     summary="User logout",
     *     description="Invalidate JWT token and logout user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="Success", type="boolean", example=true),
     *             @OA\Property(property="Status", type="integer", example=200),
     *             @OA\Property(property="Message", type="string", example="Logout successfully")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        $user = auth('api')->user();

        // Log activity before logout
        if ($user) {
            $this->logAuthActivity(
                'Logout',
                "User '{$user->name}' logged out",
                $user
            );
        }

        auth('api')->logout();
        return $this->success(ResponseMessage::LOGOUT, null, 200);
    }


}
