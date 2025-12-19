<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\ResponseAPI;

class EnforceBusinessIsolation
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

        // Check if request contains business_id and validate ownership
        if ($request->has('business_id') && $user) {
            $requestedBusinessId = $request->input('business_id');

            if ($user->business_id != $requestedBusinessId) {
                return $this->error('You are not authorized to access resources from another business', 403);
            }
        }

        // For route parameters (e.g., updating a camera, location)
        // Add business_id to request if not provided
        if ($user && $user->business_id && !$request->has('business_id')) {
            $request->merge(['business_id' => $user->business_id]);
        }

        return $next($request);
    }
}
