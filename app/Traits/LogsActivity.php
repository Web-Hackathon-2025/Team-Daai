<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Log an activity
     *
     * @param string $category - Business, Package, User, Settings, Auth, Camera, Location, etc.
     * @param string $action - Created, Updated, Deleted, Login, Logout, etc.
     * @param string $description - Human-readable description
     * @param mixed $model - Optional related model instance
     * @param array $changes - Optional old/new values for updates
     * @return ActivityLog|null
     */
    protected function logActivity(
        string $category,
        string $action,
        string $description,
        $model = null,
        array $changes = null
    ) {
        try {
            $user = Auth::guard('api')->user();
            $request = request();

            $data = [
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : 'System',
                'user_email' => $user ? $user->email : null,
                'category' => $category,
                'action' => $action,
                'description' => $description,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model ? $model->id : null,
                'changes' => $changes,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            return ActivityLog::create($data);
        } catch (\Exception $e) {
            // Silently fail - don't break application flow if logging fails
            Log::error('Activity logging failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log business-related activity
     */
    protected function logBusinessActivity(string $action, string $description, $model = null, array $changes = null)
    {
        return $this->logActivity('Business', $action, $description, $model, $changes);
    }

    /**
     * Log package-related activity
     */
    protected function logPackageActivity(string $action, string $description, $model = null, array $changes = null)
    {
        return $this->logActivity('Package', $action, $description, $model, $changes);
    }

    /**
     * Log user-related activity
     */
    protected function logUserActivity(string $action, string $description, $model = null, array $changes = null)
    {
        return $this->logActivity('User', $action, $description, $model, $changes);
    }

    /**
     * Log settings-related activity
     */
    protected function logSettingsActivity(string $action, string $description, $model = null, array $changes = null)
    {
        return $this->logActivity('Settings', $action, $description, $model, $changes);
    }

    /**
     * Log authentication-related activity
     */
    protected function logAuthActivity(string $action, string $description, $model = null)
    {
        return $this->logActivity('Auth', $action, $description, $model);
    }

    /**
     * Log camera-related activity
     */
    protected function logCameraActivity(string $action, string $description, $model = null, array $changes = null)
    {
        return $this->logActivity('Camera', $action, $description, $model, $changes);
    }

    /**
     * Log location-related activity
     */
    protected function logLocationActivity(string $action, string $description, $model = null, array $changes = null)
    {
        return $this->logActivity('Location', $action, $description, $model, $changes);
    }

    /**
     * Log role/permission-related activity
     */
    protected function logRoleActivity(string $action, string $description, $model = null, array $changes = null)
    {
        return $this->logActivity('Role', $action, $description, $model, $changes);
    }
}
