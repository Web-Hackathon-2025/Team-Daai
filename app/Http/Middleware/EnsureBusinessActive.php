<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Traits\ResponseAPI;

class EnsureBusinessActive
{
    use ResponseAPI;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Skip check for Super Admin
        if ($user && $user->hasRole('Super Admin')) {
            return $next($request);
        }

        // Check if user has a business
        if ($user && $user->business_id) {
            $business = $user->business;

            if (!$business) {
                return $this->error('Business not found', 404);
            }

            // Check if business subscription is expired
            if ($business->subscription_end_date && Carbon::now()->gt($business->subscription_end_date)) {
                return $this->error('Your subscription has expired. Please contact administrator.', 403);
            }

            // Check if business is active
            if (!$business->is_active) {
                return $this->error('Your business account is inactive. Please contact administrator.', 403);
            }
        }

        return $next($request);
    }
}
