<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Karigar Service Marketplace API",
 *     version="1.0.0",
 *     description="RESTful API for Karigar - A service marketplace connecting customers with skilled service providers (electricians, plumbers, carpenters, etc.)",
 *     @OA\Contact(
 *         email="support@karigar.com",
 *         name="Karigar API Support"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Karigar API Server"
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
 *     description="User registration, login, logout, and token management"
 * )
 *
 * @OA\Tag(
 *     name="Service Providers",
 *     description="Browse and manage service providers (Karigar profiles)"
 * )
 *
 * @OA\Tag(
 *     name="Service Requests",
 *     description="Create, manage, and track service booking requests"
 * )
 *
 * @OA\Tag(
 *     name="Reviews & Ratings",
 *     description="Customer reviews and ratings for service providers"
 * )
 *
 * @OA\Tag(
 *     name="Notifications",
 *     description="User notifications for requests, updates, and system messages"
 * )
 *
 * @OA\Tag(
 *     name="Profile",
 *     description="User profile management and settings"
 * )
 *
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard statistics and analytics for all user roles"
 * )
 *
 * @OA\Tag(
 *     name="Admin Panel",
 *     description="Administrative operations for system management"
 * )
 *
 * @OA\Tag(
 *     name="Roles & Permissions",
 *     description="Role-based access control management"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
