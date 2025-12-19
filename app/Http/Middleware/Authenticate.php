<?php

namespace App\Http\Middleware;

use App\Enums\ResponseMessage;
use App\Traits\ResponseAPI;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    use ResponseAPI;
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->error(ResponseMessage::UNAUTHORIZED, 401);
        }
        // if (! $request->expectsJson()) {
        //     return route('login');
        // }
    }
}
