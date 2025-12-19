<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\StreamSyncController;
use App\Http\Controllers\SubscriptionPackageController;
use App\Http\Controllers\SubscriptionPackageFeatureController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('login', [AuthController::class, 'login']);
Route::get('hello-world', [AuthController::class, 'helloWorld']);

// Make user Super Admin (for server-side use only)
Route::post('make-super-admin', [UserController::class, 'makeSuperAdmin']);

// Gateway Agent Sync Endpoints (No auth - use API key in agent)
Route::prefix('v1')->group(function () {
    Route::get('stream-sync/{businessId}', [StreamSyncController::class, 'syncConfig']);
    Route::post('stream-status', [StreamSyncController::class, 'updateStreamStatus']);
});

Route::middleware(['auth:api', 'business.active', 'business.isolation'])->group(function () {
    //refresh token and logout
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout']);

    //common
    Route::get('common', [CommonController::class, 'common']);

    // permissions
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::post('permission-save', [PermissionController::class, 'store']);
    Route::get('permission-by-id/{permission_id}', [PermissionController::class, 'getById']);
    Route::post('permission-update', [PermissionController::class, 'update']);
    Route::post('permission-delete', [PermissionController::class, 'destroy']);

    //Roles
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('role-save', [RoleController::class, 'store']);
    Route::get('role-by-id/{role_id}', [RoleController::class, 'getById']);
    Route::post('role-update', [RoleController::class, 'update']);
    Route::post('role-delete', [RoleController::class, 'destroy']);

    // User
    Route::get('users', [UserController::class, 'index']);
    Route::post('user-save', [UserController::class, 'store']);
    Route::get('user-by-id/{role_id}', [UserController::class, 'getById']);
    Route::post('user-update', [UserController::class, 'update']);
    Route::post('user-delete', [UserController::class, 'destroy']);

    // subscription package feature
    Route::get('subscription-package-feature', [SubscriptionPackageFeatureController::class, 'index']);
    Route::post('subscription-package-feature-save', [SubscriptionPackageFeatureController::class, 'store']);
    Route::get('subscription-package-feature-by-id/{subscription_package_feature_id}', [SubscriptionPackageFeatureController::class, 'getById']);
    Route::post('subscription-package-feature-update', [SubscriptionPackageFeatureController::class, 'update']);
    Route::post('subscription-package-feature-status', [SubscriptionPackageFeatureController::class, 'status']);
    Route::post('subscription-package-feature-delete', [SubscriptionPackageFeatureController::class, 'destroy']);

    // subscription package
    Route::get('subscription-package', [SubscriptionPackageController::class, 'index']);
    Route::post('subscription-package-save', [SubscriptionPackageController::class, 'store']);
    Route::get('subscription-package-by-id/{subscription_package_id}', [SubscriptionPackageController::class, 'getById']);
    Route::post('subscription-package-update', [SubscriptionPackageController::class, 'update']);
    Route::post('subscription-package-status', [SubscriptionPackageController::class, 'status']);
    Route::post('subscription-package-delete', [SubscriptionPackageController::class, 'destroy']);

    //business
    Route::get('business', [BusinessController::class, 'index']);
    Route::post('business-save', [BusinessController::class, 'store']);
    Route::get('business-by-id/{business_id}', [BusinessController::class, 'getById']);
    Route::post('business-update', [BusinessController::class, 'update']);
    Route::post('business-status', [BusinessController::class, 'status']);
    Route::post('business-delete', [BusinessController::class, 'destroy']);

    //locations
    Route::get('location', [LocationController::class, 'index']);
    Route::post('location-save', [LocationController::class, 'store']);
    Route::get('location-by-id/{location_id}', [LocationController::class, 'getById']);
    Route::post('location-update', [LocationController::class, 'update']);
    Route::post('location-status', [LocationController::class, 'status']);
    Route::post('location-delete', [LocationController::class, 'destroy']);


    //Companies api

    // Cameras Management
    Route::get('cameras', [CameraController::class, 'index']);
    Route::get('cameras/stats', [CameraController::class, 'stats']);
    Route::get('cameras/map', [CameraController::class, 'getMapData']);
    Route::get('cameras/manufacturers', [CameraController::class, 'getManufacturers']);
    Route::post('cameras', [CameraController::class, 'store']);
    Route::get('cameras/{id}', [CameraController::class, 'show']);
    Route::put('cameras/{id}', [CameraController::class, 'update']);
    Route::patch('cameras/{id}/comments', [CameraController::class, 'updateComments']);
    Route::patch('cameras/{id}/migrate-server', [CameraController::class, 'migrateServer']);
    Route::delete('cameras/{id}', [CameraController::class, 'destroy']);

    // Camera Streaming
    Route::post('stream/start/{id}', [StreamController::class, 'start']);
    Route::post('stream/stop/{id}', [StreamController::class, 'stop']);
    Route::post('stream/restart/{id}', [StreamController::class, 'restart']);
    Route::get('stream/status/{id}', [StreamController::class, 'status']);
    Route::get('stream/cameras', [StreamController::class, 'getStreamingCameras']);
    Route::get('stream/hls/{id}/{file}', [StreamController::class, 'serve']);

    // Legacy camera endpoints (for backward compatibility)
    Route::get('camera-count', [CameraController::class, 'stats']);
    Route::post('camera-save', [CameraController::class, 'store']);
    Route::get('camera-by-id/{camera_id}', [CameraController::class, 'show']);
    Route::post('camera-update', [CameraController::class, 'update']);
    Route::post('camera-delete', [CameraController::class, 'destroy']);

    // Activity Logs / Reports
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);
    Route::get('activity-logs-export', [ActivityLogController::class, 'export']);
    Route::get('activity-logs-filters', [ActivityLogController::class, 'filters']);
    Route::post('activity-logs-cleanup', [ActivityLogController::class, 'cleanup']);

    // Notifications / Communicator
    // Send notification (Super Admin & Business Admin)
    Route::post('notifications/send', [NotificationController::class, 'send']);

    // Get users list for notification recipients
    Route::get('notifications/users', [NotificationController::class, 'getUsersForNotification']);

    // Sent message history (Super Admin & Business Admin)
    Route::get('notifications/sent', [NotificationController::class, 'getSentMessages']);
    Route::get('notifications/sent/{id}', [NotificationController::class, 'showSentMessage']);
    Route::post('notifications/sent/delete', [NotificationController::class, 'deleteSentMessage']);

    // User notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::get('notifications/{id}', [NotificationController::class, 'show']);
    Route::post('notifications/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-unread', [NotificationController::class, 'markAsUnread']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('notifications/delete', [NotificationController::class, 'delete']);
    Route::post('notifications/delete-all-read', [NotificationController::class, 'deleteAllRead']);

    // Profile Management
    Route::get('profile', [ProfileController::class, 'show']);
    Route::post('profile', [ProfileController::class, 'vmsUpdate']);
    Route::post('profile/update', [ProfileController::class, 'update']);
    Route::post('profile/update-picture', [ProfileController::class, 'updatePicture']);
    Route::post('profile/change-password', [ProfileController::class, 'changePassword']);

    // Dashboard Stats
    Route::get('dashboard/stats', [DashboardController::class, 'getStats']);

    // Admin Settings (Business Admin Only)
    Route::prefix('settings')->group(function () {
        // Business settings
        Route::get('/', [AdminSettingsController::class, 'index']);
        Route::post('/', [AdminSettingsController::class, 'update']);

        // Email settings
        Route::get('email', [AdminSettingsController::class, 'getEmailSettings']);
        Route::post('email', [AdminSettingsController::class, 'updateEmailSettings']);

        // Payment Gateways
        Route::prefix('payment-gateways')->group(function () {
            Route::get('/', [PaymentGatewayController::class, 'index']);
            Route::post('/', [PaymentGatewayController::class, 'store']);
            Route::get('{id}', [PaymentGatewayController::class, 'show']);
            Route::post('{id}', [PaymentGatewayController::class, 'update']);
            Route::delete('{id}', [PaymentGatewayController::class, 'destroy']);
            Route::post('{id}/toggle-status', [PaymentGatewayController::class, 'toggleStatus']);
        });
    });
});
