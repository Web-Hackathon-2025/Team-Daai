<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="PinkDreams VMS API",
 *     version="1.0.0",
 *     description="Video Management System API for Camera Management, Streaming, Notifications, and Business Operations",
 *     @OA\Contact(
 *         email="support@pinkdreams.store",
 *         name="API Support"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT token in format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Login, Logout, Token Refresh"
 * )
 *
 * @OA\Tag(
 *     name="Cameras",
 *     description="Camera CRUD operations and management"
 * )
 *
 * @OA\Tag(
 *     name="Streaming",
 *     description="Camera stream management (Start, Stop, Status)"
 * )
 *
 * @OA\Tag(
 *     name="Notifications",
 *     description="Notification center and communicator"
 * )
 *
 * @OA\Tag(
 *     name="Activity Logs",
 *     description="System activity logs and reports"
 * )
 *
 * @OA\Tag(
 *     name="Business",
 *     description="Business management"
 * )
 *
 * @OA\Tag(
 *     name="Users",
 *     description="User management"
 * )
 *
 * @OA\Tag(
 *     name="Locations",
 *     description="Location management"
 * )
 *
 * @OA\Tag(
 *     name="Profile",
 *     description="User profile management"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
