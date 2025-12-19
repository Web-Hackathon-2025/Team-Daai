<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Karigar Application
|--------------------------------------------------------------------------
|
| Hyperlocal Services Marketplace API
| Connects customers with nearby service providers (Karigar)
|
*/

// Welcome Route
Route::get('/', function () {
    return 'hello world';
});
Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Welcome to Karigar API',
        'version' => '1.0.0',
        'description' => 'Hyperlocal Services Marketplace - Connecting customers with nearby service providers',
        'endpoints' => [
            'auth' => [
                'POST /api/auth/register' => 'Register new user',
                'POST /api/auth/login' => 'Login user',
            ],
            'providers' => [
                'GET /api/providers' => 'List all service providers',
                'GET /api/providers/{id}' => 'Get provider details',
            ],
            'requests' => [
                'POST /api/requests' => 'Create service request (Customer)',
                'GET /api/requests' => 'List requests',
            ],
            'reviews' => [
                'POST /api/reviews' => 'Submit review (Customer)',
                'GET /api/reviews/provider/{id}' => 'Get provider reviews',
            ]
        ]
    ]);
});


// Public Authentication Routes
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware(['auth:api'])->group(function () {
    // Authentication
    Route::post('auth/refresh', [AuthController::class, 'refresh']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // ===================================
    // SERVICE PROVIDER ROUTES
    // ===================================

    // Public Provider Routes (Customers can view)
    Route::get('providers', [ProviderController::class, 'index']);
    Route::get('providers/{id}', [ProviderController::class, 'show']);

    // Service Provider Management (Provider only)
    Route::middleware(['role:service_provider'])->group(function () {
        Route::get('providers/me', [ProviderController::class, 'getOwnProfile']);
        Route::put('providers/me', [ProviderController::class, 'updateOwnProfile']);
    });

    // ===================================
    // SERVICE REQUEST / BOOKING ROUTES
    // ===================================

    // Customer Routes
    Route::middleware(['role:customer'])->group(function () {
        Route::post('requests', [RequestController::class, 'store']);
    });

    // Both Customer & Provider can view requests
    Route::get('requests', [RequestController::class, 'index']);
    Route::get('requests/{id}', [RequestController::class, 'show']);

    // Provider Routes - Request Management
    Route::middleware(['role:service_provider'])->group(function () {
        Route::put('requests/{id}/accept', [RequestController::class, 'accept']);
        Route::put('requests/{id}/reject', [RequestController::class, 'reject']);
        Route::put('requests/{id}/reschedule', [RequestController::class, 'reschedule']);
        Route::put('requests/{id}/complete', [RequestController::class, 'complete']);
    });

    // Both can cancel
    Route::put('requests/{id}/cancel', [RequestController::class, 'cancel']);

    // ===================================
    // REVIEWS & RATINGS ROUTES
    // ===================================

    // Customer only - Submit review
    Route::middleware(['role:customer'])->group(function () {
        Route::post('reviews', [ReviewController::class, 'store']);
    });

    // Public - View reviews
    Route::get('reviews/provider/{provider_id}', [ReviewController::class, 'getProviderReviews']);

    // ===================================
    // NOTIFICATIONS
    // ===================================

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::get('notifications/{id}', [NotificationController::class, 'show']);
    Route::post('notifications/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

    // ===================================
    // PROFILE MANAGEMENT
    // ===================================

    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/change-password', [ProfileController::class, 'changePassword']);

    // ===================================
    // DASHBOARD
    // ===================================

    Route::get('dashboard/stats', [DashboardController::class, 'getStats']);

    // ===================================
    // ADMIN ROUTES (Optional - Bonus)
    // ===================================

    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        // User Management
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{id}', [UserController::class, 'show']);
        Route::put('users/{id}/approve', [UserController::class, 'approve']);
        Route::put('users/{id}/suspend', [UserController::class, 'suspend']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);

        // Platform Stats
        Route::get('stats', [DashboardController::class, 'getAdminStats']);

        // Roles & Permissions
        Route::get('roles', [RoleController::class, 'index']);
        Route::post('roles', [RoleController::class, 'store']);
        Route::get('permissions', [PermissionController::class, 'index']);
    });
});
