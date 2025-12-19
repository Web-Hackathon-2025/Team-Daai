<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMessage;
use App\Models\User;
use App\Traits\ResponseAPI;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    use ResponseAPI, LogsActivity;

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Authentication"},
     *     summary="Register new user",
     *     description="Register a new customer or service provider",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="role", type="string", enum={"customer", "service_provider"}, example="customer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="role", type="string")
     *                 ),
     *                 @OA\Property(property="token", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error")
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:customer,service_provider',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign role
            $user->assignRole($request->role);

            // Generate token
            $token = JWTAuth::fromUser($user);

            DB::commit();

            // Load relationships
            $user->load('roles');

            // Log activity
            $this->logAuthActivity(
                'Register',
                "User '{$user->name}' registered as {$request->role}",
                $user
            );

            return $this->success('User registered successfully', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $request->role,
                ],
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Authentication"},
     *     summary="User login",
     *     description="Authenticate user and return JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="role", type="string")
     *                 ),
     *                 @OA\Property(property="token", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request)
    {
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
                return $this->error('Invalid email or password', 401);
            }
        } catch (JWTException $e) {
            return $this->error('Could not create token', 500);
        }

        // Retrieve the Eloquent user instance from the token
        try {
            $user = JWTAuth::setToken($token)->toUser();
        } catch (\Exception $e) {
            // invalidate token if anything goes wrong
            JWTAuth::invalidate($token);
            return $this->error('Authentication failed', 401);
        }

        // Load relationships
        if ($user) {
            $user->load(['roles', 'roles.permissions']);
            $user->roles->each->makeHidden('pivot');
        }

        // Log activity
        $this->logAuthActivity(
            'Login',
            "User '{$user->name}' logged in successfully",
            $user
        );

        return $this->success('Login successful', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()->name ?? null,
            ],
            'token' => $token,
        ], 200);
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
